package utils;

import com.jcraft.jsch.JSch;
import com.jcraft.jsch.Session;

/**
 * Opens an SSH tunnel to forward a local port to a remote MySQL instance.
 * Used when app.mode=local and db.url points to a remote host that is
 * firewalled — the tunnel lets HikariCP connect via localhost.
 */
public class SshTunnel {

    private static Session session;
    private static int localPort = -1;

    /**
     * Opens the tunnel if SSH config is present and db.url targets the remote host.
     * Returns the local port to use for JDBC, or -1 if no tunnel was needed.
     */
    public static int open() {
        String sshHost = AppConfig.get("server.host");
        String sshUser = AppConfig.get("server.ssh_user", "seji");
        String sshKeyPath = AppConfig.get("ssh.key_path", defaultKeyPath());
        String sshPassphrase = AppConfig.get("ssh.passphrase", "");
        int sshPort = Integer.parseInt(AppConfig.get("ssh.port", "22"));
        int remoteDbPort = Integer.parseInt(AppConfig.get("ssh.remote_db_port", "3306"));
        int desiredLocalPort = Integer.parseInt(AppConfig.get("ssh.local_port", "3307"));

        if (sshHost == null || sshHost.isEmpty()) {
            return -1;
        }

        // Only tunnel if the db.url actually targets the remote host
        String dbUrl = AppConfig.getDbUrl();
        boolean needsTunnel = dbUrl.contains(sshHost) || "true".equalsIgnoreCase(AppConfig.get("ssh.force_tunnel"));
        if (!needsTunnel) {
            return -1;
        }

        try {
            JSch jsch = new JSch();

            // Load private key
            java.io.File keyFile = new java.io.File(sshKeyPath);
            if (keyFile.exists()) {
                if (sshPassphrase.isEmpty()) {
                    jsch.addIdentity(keyFile.getAbsolutePath());
                } else {
                    jsch.addIdentity(keyFile.getAbsolutePath(), sshPassphrase);
                }
            } else {
                System.err.println("⚠ SSH key not found: " + sshKeyPath);
                return -1;
            }

            session = jsch.getSession(sshUser, sshHost, sshPort);
            session.setConfig("StrictHostKeyChecking", "no");
            session.setConfig("ServerAliveInterval", "30");
            session.setConfig("ServerAliveCountMax", "3");
            session.setTimeout(10_000);

            System.out.println("🔗 Opening SSH tunnel to " + sshUser + "@" + sshHost + ":" + sshPort + " ...");
            session.connect(10_000);

            // Forward local port → remote MySQL
            localPort = session.setPortForwardingL(desiredLocalPort, "127.0.0.1", remoteDbPort);
            System.out.println("✅ SSH tunnel open: localhost:" + localPort + " → " + sshHost + ":" + remoteDbPort);
            return localPort;

        } catch (Exception e) {
            System.err.println("❌ SSH tunnel failed: " + e.getMessage());
            close();
            return -1;
        }
    }

    /** Close the tunnel session (call on app exit). */
    public static void close() {
        if (session != null && session.isConnected()) {
            try {
                session.disconnect();
                System.out.println("SSH tunnel closed.");
            } catch (Exception e) {
                // ignore
            }
            session = null;
        }
    }

    public static boolean isOpen() {
        return session != null && session.isConnected() && localPort > 0;
    }

    public static int getLocalPort() {
        return localPort;
    }

    private static String defaultKeyPath() {
        return System.getProperty("user.home") + "/.ssh/id_ed25519";
    }
}
