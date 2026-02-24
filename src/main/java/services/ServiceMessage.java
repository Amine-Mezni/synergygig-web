package services;

import com.google.gson.*;
import entities.Message;
import utils.ApiClient;
import utils.AppConfig;
import utils.MyDatabase;

import java.sql.*;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

public class ServiceMessage implements IService<Message> {

    private Connection connection;
    private final boolean useApi;

    public ServiceMessage() {
        useApi = AppConfig.isApiMode();
        if (!useApi) {
            connection = MyDatabase.getInstance().getConnection();
        }
    }

    // ==================== JSON helpers ====================

    private Message jsonToMessage(JsonObject obj) {
        Timestamp timestamp = null;
        if (obj.has("timestamp") && !obj.get("timestamp").isJsonNull()) {
            timestamp = Timestamp.valueOf(obj.get("timestamp").getAsString().replace("T", " "));
        }
        return new Message(
                obj.get("id").getAsInt(),
                obj.get("sender_id").getAsInt(),
                obj.get("room_id").getAsInt(),
                obj.get("content").getAsString(),
                timestamp
        );
    }

    private List<Message> jsonArrayToMessages(JsonElement el) {
        List<Message> messages = new ArrayList<>();
        if (el != null && el.isJsonArray()) {
            for (JsonElement item : el.getAsJsonArray()) {
                messages.add(jsonToMessage(item.getAsJsonObject()));
            }
        }
        return messages;
    }

    // ==================== CRUD ====================

    @Override
    public void ajouter(Message message) throws SQLException {
        if (useApi) {
            Map<String, Object> body = new HashMap<>();
            body.put("sender_id", message.getSenderId());
            body.put("room_id", message.getRoomId());
            body.put("content", message.getContent());
            ApiClient.post("/messages", body);
            return;
        }
        String req = "INSERT INTO messages (sender_id, room_id, content) VALUES (?, ?, ?)";
        try (PreparedStatement ps = connection.prepareStatement(req)) {
            ps.setInt(1, message.getSenderId());
            ps.setInt(2, message.getRoomId());
            ps.setString(3, message.getContent());
            ps.executeUpdate();
        }
    }

    @Override
    public void modifier(Message message) throws SQLException {
        if (useApi) {
            Map<String, Object> body = new HashMap<>();
            body.put("content", message.getContent());
            ApiClient.put("/messages/" + message.getId(), body);
            return;
        }
        String req = "UPDATE messages SET content=? WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(req)) {
            ps.setString(1, message.getContent());
            ps.setInt(2, message.getId());
            ps.executeUpdate();
        }
    }

    @Override
    public void supprimer(int id) throws SQLException {
        if (useApi) {
            ApiClient.delete("/messages/" + id);
            return;
        }
        String req = "DELETE FROM messages WHERE id=?";
        try (PreparedStatement ps = connection.prepareStatement(req)) {
            ps.setInt(1, id);
            ps.executeUpdate();
        }
    }

    @Override
    public List<Message> recuperer() throws SQLException {
        if (useApi) {
            return jsonArrayToMessages(ApiClient.get("/messages"));
        }
        List<Message> messages = new ArrayList<>();
        String req = "SELECT * FROM messages";
        try (PreparedStatement ps = connection.prepareStatement(req);
             ResultSet rs = ps.executeQuery()) {
            while (rs.next()) {
                Message msg = new Message(
                        rs.getInt("id"),
                        rs.getInt("sender_id"),
                        rs.getInt("room_id"),
                        rs.getString("content"),
                        rs.getTimestamp("timestamp"));
                messages.add(msg);
            }
        }
        return messages;
    }

    public List<Message> getByRoom(int roomId) throws SQLException {
        if (useApi) {
            return jsonArrayToMessages(ApiClient.get("/messages/room/" + roomId));
        }
        List<Message> messages = new ArrayList<>();
        String req = "SELECT * FROM messages WHERE room_id=? ORDER BY timestamp ASC";
        try (PreparedStatement ps = connection.prepareStatement(req)) {
            ps.setInt(1, roomId);
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    Message msg = new Message(
                            rs.getInt("id"),
                            rs.getInt("sender_id"),
                            rs.getInt("room_id"),
                            rs.getString("content"),
                            rs.getTimestamp("timestamp"));
                    messages.add(msg);
                }
            }
        }
        return messages;
    }

    /**
     * Returns the latest message timestamp for each room in a single query (JDBC)
     * or with per-room API calls (API mode — only fetches last element from each response).
     */
    public Map<Integer, java.sql.Timestamp> getLatestTimestamps(List<Integer> roomIds) throws SQLException {
        Map<Integer, java.sql.Timestamp> result = new HashMap<>();
        if (roomIds == null || roomIds.isEmpty()) return result;

        if (useApi) {
            // API mode: fall back to per-room calls, but only extract the last timestamp
            for (int roomId : roomIds) {
                try {
                    List<Message> msgs = getByRoom(roomId);
                    if (!msgs.isEmpty()) {
                        Message last = msgs.get(msgs.size() - 1);
                        if (last.getTimestamp() != null) {
                            result.put(roomId, last.getTimestamp());
                        }
                    }
                } catch (Exception ignored) { /* skip rooms that fail */ }
            }
            return result;
        }

        // JDBC mode: single GROUP BY query
        String sql = "SELECT room_id, MAX(timestamp) AS latest FROM messages WHERE room_id IN ("
                + String.join(",", java.util.Collections.nCopies(roomIds.size(), "?"))
                + ") GROUP BY room_id";
        try (PreparedStatement ps = connection.prepareStatement(sql)) {
            for (int i = 0; i < roomIds.size(); i++) {
                ps.setInt(i + 1, roomIds.get(i));
            }
            try (ResultSet rs = ps.executeQuery()) {
                while (rs.next()) {
                    result.put(rs.getInt("room_id"), rs.getTimestamp("latest"));
                }
            }
        }
        return result;
    }

    /** Returns total message count without loading all rows. */
    public int count() throws SQLException {
        if (useApi) {
            // API mode: fall back to recuperer().size() (no dedicated count endpoint)
            return recuperer().size();
        }
        try (Statement st = connection.createStatement();
             ResultSet rs = st.executeQuery("SELECT COUNT(*) FROM messages")) {
            return rs.next() ? rs.getInt(1) : 0;
        }
    }
}
