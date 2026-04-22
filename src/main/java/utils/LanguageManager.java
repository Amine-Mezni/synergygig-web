package utils;

import java.io.*;
import java.util.*;

/**
 * Singleton language manager that loads i18n resource bundles and provides
 * translated strings throughout the application.
 *
 * Supported languages: en (English), fr (French), ar (Arabic).
 * The selected language is persisted via {@link AppConfig} under "app.language".
 */
public class LanguageManager {

    private static final LanguageManager INSTANCE = new LanguageManager();

    /** Ordered list of supported language codes. */
    public static final String[] SUPPORTED_CODES = {"en", "fr", "ar"};

    /** Display names matching SUPPORTED_CODES (always in their native form). */
    public static final String[] DISPLAY_NAMES = {
            "\uD83C\uDDEC\uD83C\uDDE7 English",
            "\uD83C\uDDEB\uD83C\uDDF7 Français",
            "\uD83C\uDDF8\uD83C\uDDE6 العربية"
    };

    private String currentCode;
    private Properties messages;

    /** Listeners notified when the language changes. */
    private final List<Runnable> listeners = new ArrayList<>();

    private LanguageManager() {
        currentCode = AppConfig.get("app.language", "en");
        messages = loadBundle(currentCode);
    }

    public static LanguageManager getInstance() {
        return INSTANCE;
    }

    // ── Public API ──────────────────────────────────────────

    /** Get the current language code (e.g. "en", "fr", "ar"). */
    public String getCode() {
        return currentCode;
    }

    /** Check if current language is RTL (Arabic). */
    public boolean isRTL() {
        return "ar".equals(currentCode);
    }

    /**
     * Translate a key. Returns the key itself if no translation is found.
     */
    public String get(String key) {
        return messages.getProperty(key, key);
    }

    /**
     * Translate a key with a fallback default.
     */
    public String get(String key, String defaultValue) {
        return messages.getProperty(key, defaultValue);
    }

    /**
     * Switch language. Persists to config and notifies listeners.
     */
    public void setLanguage(String code) {
        if (code == null || code.equals(currentCode)) return;
        // Validate
        boolean valid = false;
        for (String c : SUPPORTED_CODES) {
            if (c.equals(code)) { valid = true; break; }
        }
        if (!valid) return;

        currentCode = code;
        messages = loadBundle(code);

        // Persist
        AppConfig.set("app.language", code);
        try { AppConfig.save(); } catch (Exception ignored) {}

        // Notify listeners
        for (Runnable listener : new ArrayList<>(listeners)) {
            try { listener.run(); } catch (Exception ignored) {}
        }
    }

    /**
     * Register a listener that fires whenever the language changes.
     */
    public void addChangeListener(Runnable listener) {
        if (listener != null) listeners.add(listener);
    }

    public void removeChangeListener(Runnable listener) {
        listeners.remove(listener);
    }

    /**
     * Get the display name for the current language.
     */
    public String getCurrentDisplayName() {
        for (int i = 0; i < SUPPORTED_CODES.length; i++) {
            if (SUPPORTED_CODES[i].equals(currentCode)) return DISPLAY_NAMES[i];
        }
        return DISPLAY_NAMES[0];
    }

    // ── Internal ────────────────────────────────────────────

    private Properties loadBundle(String code) {
        Properties props = new Properties();
        String path = "/i18n/messages_" + code + ".properties";
        try (InputStream is = getClass().getResourceAsStream(path)) {
            if (is != null) {
                // Use InputStreamReader with UTF-8 to handle Unicode properly
                props.load(new InputStreamReader(is, "UTF-8"));
            } else {
                System.err.println("[LanguageManager] Bundle not found: " + path);
            }
        } catch (Exception e) {
            System.err.println("[LanguageManager] Error loading bundle: " + e.getMessage());
        }
        return props;
    }
}
