package controllers;

import com.google.gson.Gson;
import com.google.gson.JsonObject;
import com.google.gson.JsonParser;
import entities.*;
import javafx.application.Platform;
import javafx.collections.FXCollections;
import javafx.fxml.FXML;
import javafx.geometry.Insets;
import javafx.geometry.Pos;
import javafx.scene.Node;
import javafx.scene.control.*;
import javafx.scene.control.cell.PropertyValueFactory;
import javafx.scene.layout.*;
import javafx.stage.FileChooser;
import services.*;
import utils.*;

import java.awt.Desktop;
import java.io.File;
import java.sql.Date;
import java.sql.SQLException;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.stream.Collectors;

/**
 * Controller for the Offer & Contract Management module.
 * 5 tabs: Marketplace, My Offers, Applications, Contracts, AI Assistant.
 */
public class OfferContractController {

    // ── FXML injected fields ──
    @FXML private BorderPane rootPane;
    @FXML private HBox tabBar;
    @FXML private StackPane contentArea;
    @FXML private Label headerTitle, headerRole;

    // Tab 1: Marketplace
    @FXML private VBox marketplaceView;
    @FXML private TextField marketSearchField;
    @FXML private ComboBox<String> filterType, filterCurrency;
    @FXML private HBox marketFilterBar;
    @FXML private FlowPane offerGrid;
    @FXML private Button filterMktAll, filterMktFullTime, filterMktFreelance, filterMktInternship, filterMktContract;

    // Tab 2: My Offers
    @FXML private VBox myOffersView;
    @FXML private VBox myOffersList;
    @FXML private Button btnNewOffer;

    // Tab 3: Applications
    @FXML private VBox applicationsView;
    @FXML private VBox applicationsList;
    @FXML private ComboBox<String> filterAppStatus;

    // Tab 4: Contracts
    @FXML private VBox contractsView;
    @FXML private TableView<Contract> contractsTable;
    @FXML private TableColumn<Contract, Integer> colContractId;
    @FXML private TableColumn<Contract, String> colContractOffer, colContractParty, colContractAmount,
            colContractStatus, colContractRisk, colContractDates, colContractActions;
    @FXML private ComboBox<String> filterContractStatus;

    // Tab 5: AI Assistant
    @FXML private VBox aiAssistantView;
    @FXML private VBox aiChatBox;
    @FXML private ScrollPane aiChatScroll;
    @FXML private TextField aiInputField;
    @FXML private Label aiStatusLabel;

    // ── Services ──
    private final ServiceOffer serviceOffer = new ServiceOffer();
    private final ServiceJobApplication serviceApp = new ServiceJobApplication();
    private final ServiceContract serviceContract = new ServiceContract();
    private final ServiceUser serviceUser = new ServiceUser();
    private final ServiceNotification serviceNotification = new ServiceNotification();
    private ZAIService zaiService;

    // ── State ──
    private User currentUser;
    private boolean isOwnerOrAdmin;
    private Button activeTab;
    private String currentMarketFilter = "ALL";
    private Map<Integer, String> userNameMap = new HashMap<>();
    private Map<Integer, Offer> offerMap = new HashMap<>();
    private List<Offer> allOffers = new ArrayList<>();
    private List<Map<String, String>> aiChatHistory = new ArrayList<>();
    private Contract selectedAiContract;

    @FXML
    public void initialize() {
        currentUser = SessionManager.getInstance().getCurrentUser();
        String role = currentUser != null ? currentUser.getRole() : "";
        isOwnerOrAdmin = "ADMIN".equals(role) || "PROJECT_OWNER".equals(role) || "HR_MANAGER".equals(role);

        headerRole.setText(isOwnerOrAdmin ? "Owner View" : "Applicant View");

        zaiService = new ZAIService();
        loadUserNames();
        loadOfferMap();

        // Setup filter combos
        filterType.setItems(FXCollections.observableArrayList("All", "FULL_TIME", "PART_TIME", "FREELANCE", "INTERNSHIP", "CONTRACT"));
        filterType.setValue("All");
        filterType.setOnAction(e -> refreshMarketplace());

        filterCurrency.setItems(FXCollections.observableArrayList("All", "USD", "EUR", "GBP", "TND"));
        filterCurrency.setValue("All");
        filterCurrency.setOnAction(e -> refreshMarketplace());

        filterAppStatus.setItems(FXCollections.observableArrayList("All", "PENDING", "REVIEWED", "SHORTLISTED", "ACCEPTED", "REJECTED", "WITHDRAWN"));
        filterAppStatus.setValue("All");

        filterContractStatus.setItems(FXCollections.observableArrayList("All", "DRAFT", "PENDING_SIGNATURE", "ACTIVE", "COMPLETED", "TERMINATED", "DISPUTED"));
        filterContractStatus.setValue("All");

        // Build tabs
        List<String[]> tabs = new ArrayList<>();
        tabs.add(new String[]{"🏪", "Marketplace"});
        if (isOwnerOrAdmin) {
            tabs.add(new String[]{"📋", "My Offers"});
        }
        tabs.add(new String[]{"📩", "Applications"});
        tabs.add(new String[]{"📄", "Contracts"});
        tabs.add(new String[]{"🤖", "AI Assistant"});

        for (String[] tab : tabs) {
            Button btn = new Button(tab[0] + "  " + tab[1]);
            btn.getStyleClass().add("oc-tab-btn");
            btn.setOnAction(e -> {
                SoundManager.getInstance().play(SoundManager.TAB_SWITCH);
                switchTab(btn, tab[1]);
            });
            tabBar.getChildren().add(btn);
        }

        setupContractsTable();

        // Show first tab
        if (!tabBar.getChildren().isEmpty()) {
            Button first = (Button) tabBar.getChildren().get(0);
            switchTab(first, "Marketplace");
        }
    }

    // ==================== Data Loading ====================

    private void loadUserNames() {
        try {
            for (User u : serviceUser.recuperer()) {
                userNameMap.put(u.getId(), u.getFirstName() + " " + u.getLastName());
            }
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private void loadOfferMap() {
        try {
            allOffers = serviceOffer.recuperer();
            offerMap.clear();
            for (Offer o : allOffers) offerMap.put(o.getId(), o);
        } catch (SQLException e) { e.printStackTrace(); }
    }

    private String getUserName(int userId) {
        return userNameMap.getOrDefault(userId, "User #" + userId);
    }

    // ==================== Tab Switching ====================

    private void switchTab(Button btn, String tabName) {
        if (activeTab != null) activeTab.getStyleClass().remove("oc-tab-active");
        btn.getStyleClass().add("oc-tab-active");
        activeTab = btn;

        // Hide all views
        marketplaceView.setVisible(false); marketplaceView.setManaged(false);
        myOffersView.setVisible(false); myOffersView.setManaged(false);
        applicationsView.setVisible(false); applicationsView.setManaged(false);
        contractsView.setVisible(false); contractsView.setManaged(false);
        aiAssistantView.setVisible(false); aiAssistantView.setManaged(false);

        switch (tabName) {
            case "Marketplace":
                marketplaceView.setVisible(true); marketplaceView.setManaged(true);
                refreshMarketplace();
                break;
            case "My Offers":
                myOffersView.setVisible(true); myOffersView.setManaged(true);
                refreshMyOffers();
                break;
            case "Applications":
                applicationsView.setVisible(true); applicationsView.setManaged(true);
                refreshApplications();
                break;
            case "Contracts":
                contractsView.setVisible(true); contractsView.setManaged(true);
                refreshContracts();
                break;
            case "AI Assistant":
                aiAssistantView.setVisible(true); aiAssistantView.setManaged(true);
                break;
        }
    }

    // ================================================================
    // TAB 1: MARKETPLACE
    // ================================================================

    private void refreshMarketplace() {
        offerGrid.getChildren().clear();
        loadOfferMap();

        String searchText = marketSearchField.getText() != null ? marketSearchField.getText().toLowerCase() : "";
        String typeFilter = filterType.getValue();
        String currFilter = filterCurrency.getValue();

        List<Offer> filtered = allOffers.stream()
                .filter(o -> "PUBLISHED".equals(o.getStatus()))
                .filter(o -> searchText.isEmpty()
                        || o.getTitle().toLowerCase().contains(searchText)
                        || (o.getDescription() != null && o.getDescription().toLowerCase().contains(searchText))
                        || (o.getRequiredSkills() != null && o.getRequiredSkills().toLowerCase().contains(searchText)))
                .filter(o -> "All".equals(typeFilter) || typeFilter.equals(o.getOfferType()))
                .filter(o -> "All".equals(currFilter) || currFilter.equals(o.getCurrency()))
                .filter(o -> "ALL".equals(currentMarketFilter) || currentMarketFilter.equals(o.getOfferType()))
                .collect(Collectors.toList());

        if (filtered.isEmpty()) {
            Label empty = new Label("No open offers found.");
            empty.getStyleClass().add("oc-empty-label");
            offerGrid.getChildren().add(empty);
            return;
        }

        for (Offer o : filtered) {
            offerGrid.getChildren().add(createOfferCard(o));
        }
    }

    private VBox createOfferCard(Offer o) {
        VBox card = new VBox(8);
        card.getStyleClass().add("oc-offer-card");
        card.setPrefWidth(300);
        card.setPadding(new Insets(16));

        // Type badge
        Label badge = new Label(o.getOfferType());
        badge.getStyleClass().addAll("oc-badge", "oc-badge-" + o.getOfferType().toLowerCase().replace("_", "-"));

        Label title = new Label(o.getTitle());
        title.getStyleClass().add("oc-card-title");
        title.setWrapText(true);

        Label desc = new Label(o.getDescription() != null ?
                (o.getDescription().length() > 120 ? o.getDescription().substring(0, 120) + "..." : o.getDescription())
                : "No description");
        desc.getStyleClass().add("oc-card-desc");
        desc.setWrapText(true);
        desc.setMaxHeight(60);

        HBox metaRow = new HBox(12);
        metaRow.setAlignment(Pos.CENTER_LEFT);
        Label amount = new Label(String.format("%s %.0f", o.getCurrency(), o.getAmount()));
        amount.getStyleClass().add("oc-card-amount");
        Label location = new Label(o.getLocation() != null ? "📍 " + o.getLocation() : "");
        location.getStyleClass().add("oc-card-meta");
        metaRow.getChildren().addAll(amount, location);

        Label skills = new Label(o.getRequiredSkills() != null ? "🔧 " + o.getRequiredSkills() : "");
        skills.getStyleClass().add("oc-card-skills");
        skills.setWrapText(true);

        Label owner = new Label("By: " + getUserName(o.getOwnerId()));
        owner.getStyleClass().add("oc-card-meta");

        HBox actions = new HBox(8);
        actions.setAlignment(Pos.CENTER_RIGHT);

        // Don't show Apply if it's the user's own offer
        if (currentUser != null && o.getOwnerId() != currentUser.getId()) {
            Button btnApply = new Button("Apply");
            btnApply.getStyleClass().add("oc-btn-primary");
            btnApply.setOnAction(e -> showApplyDialog(o));
            actions.getChildren().add(btnApply);
        }

        Button btnView = new Button("Details");
        btnView.getStyleClass().add("oc-btn-secondary");
        btnView.setOnAction(e -> showOfferDetails(o));
        actions.getChildren().add(btnView);

        card.getChildren().addAll(badge, title, desc, metaRow, skills, owner, actions);
        return card;
    }

    @FXML private void onMarketSearchChanged() { refreshMarketplace(); }
    @FXML private void filterMarketAll() { setMarketFilter("ALL", filterMktAll); }
    @FXML private void filterMarketType(javafx.event.ActionEvent e) {
        Button btn = (Button) e.getSource();
        String type = btn.getText().toUpperCase().replace("-", "_");
        setMarketFilter(type, btn);
    }

    private void setMarketFilter(String type, Button btn) {
        currentMarketFilter = type;
        for (Node n : marketFilterBar.getChildren()) {
            n.getStyleClass().remove("oc-filter-active");
        }
        btn.getStyleClass().add("oc-filter-active");
        refreshMarketplace();
    }

    private void showOfferDetails(Offer o) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION);
        alert.setTitle("Offer Details");
        alert.setHeaderText(o.getTitle());

        String details = String.format(
            "Type: %s\nStatus: %s\nLocation: %s\nAmount: %s %.2f\n\nRequired Skills:\n%s\n\nDescription:\n%s\n\nPosted by: %s\nDates: %s → %s",
            o.getOfferType(), o.getStatus(), o.getLocation(), o.getCurrency(), o.getAmount(),
            o.getRequiredSkills() != null ? o.getRequiredSkills() : "N/A",
            o.getDescription() != null ? o.getDescription() : "N/A",
            getUserName(o.getOwnerId()),
            o.getStartDate() != null ? o.getStartDate().toString() : "TBD",
            o.getEndDate() != null ? o.getEndDate().toString() : "TBD"
        );
        alert.setContentText(details);
        alert.getDialogPane().setMinWidth(500);
        alert.showAndWait();
    }

    // ================================================================
    // TAB 2: MY OFFERS (owner/admin only)
    // ================================================================

    private void refreshMyOffers() {
        myOffersList.getChildren().clear();
        try {
            List<Offer> offers = serviceOffer.getByOwner(currentUser.getId());
            if (offers.isEmpty()) {
                Label empty = new Label("You haven't posted any offers yet. Click '+ New Offer' to get started.");
                empty.getStyleClass().add("oc-empty-label");
                myOffersList.getChildren().add(empty);
                return;
            }
            for (Offer o : offers) {
                myOffersList.getChildren().add(createMyOfferRow(o));
            }
        } catch (SQLException e) {
            showError("Failed to load offers: " + e.getMessage());
        }
    }

    private HBox createMyOfferRow(Offer o) {
        HBox row = new HBox(12);
        row.getStyleClass().add("oc-list-row");
        row.setAlignment(Pos.CENTER_LEFT);
        row.setPadding(new Insets(12, 16, 12, 16));

        Label badge = new Label(o.getOfferType());
        badge.getStyleClass().addAll("oc-badge", "oc-badge-" + o.getOfferType().toLowerCase().replace("_", "-"));
        badge.setMinWidth(90);

        VBox info = new VBox(2);
        HBox.setHgrow(info, Priority.ALWAYS);
        Label title = new Label(o.getTitle());
        title.getStyleClass().add("oc-row-title");
        Label meta = new Label(String.format("%s %.0f • %s • %s",
                o.getCurrency(), o.getAmount(), o.getLocation() != null ? o.getLocation() : "Remote", o.getStatus()));
        meta.getStyleClass().add("oc-row-meta");
        info.getChildren().addAll(title, meta);

        Label status = new Label(o.getStatus());
        status.getStyleClass().addAll("oc-status-badge", "oc-status-" + o.getStatus().toLowerCase());

        Button btnEdit = new Button("Edit");
        btnEdit.getStyleClass().add("oc-btn-secondary");
        btnEdit.setOnAction(e -> showEditOfferDialog(o));

        Button btnDelete = new Button("Delete");
        btnDelete.getStyleClass().add("oc-btn-danger");
        btnDelete.setOnAction(e -> deleteOffer(o));

        Button btnToggle = new Button(o.getStatus().equals("DRAFT") ? "Publish" : o.getStatus().equals("PUBLISHED") ? "Close" : o.getStatus());
        btnToggle.getStyleClass().add("oc-btn-primary");
        btnToggle.setOnAction(e -> toggleOfferStatus(o));

        row.getChildren().addAll(badge, info, status, btnToggle, btnEdit, btnDelete);
        return row;
    }

    @FXML
    private void showNewOfferDialog() {
        showOfferFormDialog(null);
    }

    private void showEditOfferDialog(Offer o) {
        showOfferFormDialog(o);
    }

    private void showOfferFormDialog(Offer existing) {
        Dialog<Offer> dialog = new Dialog<>();
        dialog.setTitle(existing == null ? "New Offer" : "Edit Offer");
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);
        dialog.getDialogPane().setMinWidth(500);

        GridPane grid = new GridPane();
        grid.setHgap(10);
        grid.setVgap(10);
        grid.setPadding(new Insets(16));

        TextField tfTitle = new TextField(existing != null ? existing.getTitle() : "");
        tfTitle.setPromptText("Offer title");

        TextArea taDesc = new TextArea(existing != null ? existing.getDescription() : "");
        taDesc.setPromptText("Description");
        taDesc.setPrefRowCount(4);

        ComboBox<String> cbType = new ComboBox<>(FXCollections.observableArrayList("FULL_TIME", "PART_TIME", "FREELANCE", "INTERNSHIP", "CONTRACT"));
        cbType.setValue(existing != null ? existing.getOfferType() : "FREELANCE");

        TextField tfSkills = new TextField(existing != null ? existing.getRequiredSkills() : "");
        tfSkills.setPromptText("Java, Python, React...");

        TextField tfLocation = new TextField(existing != null ? existing.getLocation() : "");
        tfLocation.setPromptText("Remote / City");

        TextField tfAmount = new TextField(existing != null ? String.valueOf(existing.getAmount()) : "0");
        ComboBox<String> cbCurrency = new ComboBox<>(FXCollections.observableArrayList("USD", "EUR", "GBP", "TND"));
        cbCurrency.setValue(existing != null ? existing.getCurrency() : "USD");

        DatePicker dpStart = new DatePicker(existing != null && existing.getStartDate() != null ? existing.getStartDate().toLocalDate() : null);
        DatePicker dpEnd = new DatePicker(existing != null && existing.getEndDate() != null ? existing.getEndDate().toLocalDate() : null);

        grid.add(new Label("Title:"), 0, 0);     grid.add(tfTitle, 1, 0, 2, 1);
        grid.add(new Label("Description:"), 0, 1); grid.add(taDesc, 1, 1, 2, 1);
        grid.add(new Label("Type:"), 0, 2);       grid.add(cbType, 1, 2);
        grid.add(new Label("Skills:"), 0, 3);     grid.add(tfSkills, 1, 3, 2, 1);
        grid.add(new Label("Location:"), 0, 4);   grid.add(tfLocation, 1, 4);
        grid.add(new Label("Amount:"), 0, 5);     grid.add(tfAmount, 1, 5);
        grid.add(new Label("Currency:"), 0, 6);   grid.add(cbCurrency, 1, 6);
        grid.add(new Label("Start Date:"), 0, 7); grid.add(dpStart, 1, 7);
        grid.add(new Label("End Date:"), 0, 8);   grid.add(dpEnd, 1, 8);

        dialog.getDialogPane().setContent(grid);

        dialog.setResultConverter(btn -> {
            if (btn == ButtonType.OK) {
                try {
                    Offer o = existing != null ? existing : new Offer();
                    o.setTitle(tfTitle.getText().trim());
                    o.setDescription(taDesc.getText().trim());
                    o.setOfferType(cbType.getValue());
                    o.setRequiredSkills(tfSkills.getText().trim());
                    o.setLocation(tfLocation.getText().trim());
                    o.setAmount(Double.parseDouble(tfAmount.getText().trim()));
                    o.setCurrency(cbCurrency.getValue());
                    o.setOwnerId(currentUser.getId());
                    if (existing == null) o.setStatus("DRAFT");
                    o.setStartDate(dpStart.getValue() != null ? Date.valueOf(dpStart.getValue()) : null);
                    o.setEndDate(dpEnd.getValue() != null ? Date.valueOf(dpEnd.getValue()) : null);
                    return o;
                } catch (Exception ex) {
                    showError("Invalid input: " + ex.getMessage());
                }
            }
            return null;
        });

        dialog.showAndWait().ifPresent(o -> {
            try {
                if (existing == null) {
                    serviceOffer.ajouter(o);
                    showInfo("Offer created!");
                } else {
                    serviceOffer.modifier(o);
                    showInfo("Offer updated!");
                }
                SoundManager.getInstance().play(SoundManager.MESSAGE_SENT);
                refreshMyOffers();
            } catch (SQLException e) {
                showError("Save failed: " + e.getMessage());
            }
        });
    }

    private void toggleOfferStatus(Offer o) {
        try {
            if ("DRAFT".equals(o.getStatus())) {
                o.setStatus("PUBLISHED");
                serviceOffer.modifier(o);
                showInfo("Offer published!");
            } else if ("PUBLISHED".equals(o.getStatus())) {
                o.setStatus("COMPLETED");
                serviceOffer.modifier(o);
                showInfo("Offer closed.");
            }
            SoundManager.getInstance().play(SoundManager.MESSAGE_SENT);
            refreshMyOffers();
        } catch (SQLException e) {
            showError("Status change failed: " + e.getMessage());
        }
    }

    private void deleteOffer(Offer o) {
        Alert confirm = new Alert(Alert.AlertType.CONFIRMATION, "Delete offer '" + o.getTitle() + "'? This will also delete all applications and contracts.", ButtonType.YES, ButtonType.NO);
        confirm.showAndWait().ifPresent(btn -> {
            if (btn == ButtonType.YES) {
                try {
                    serviceOffer.supprimer(o.getId());
                    SoundManager.getInstance().play(SoundManager.ERROR);
                    refreshMyOffers();
                } catch (SQLException e) {
                    showError("Delete failed: " + e.getMessage());
                }
            }
        });
    }

    // ================================================================
    // TAB 3: APPLICATIONS
    // ================================================================

    private void refreshApplications() {
        applicationsList.getChildren().clear();
        try {
            List<JobApplication> apps;
            if (isOwnerOrAdmin) {
                // Show all applications for my offers
                apps = serviceApp.recuperer();
            } else {
                // Show only my applications
                apps = serviceApp.getByUser(currentUser.getId());
            }

            String statusFilter = filterAppStatus.getValue();
            if (!"All".equals(statusFilter)) {
                apps = apps.stream().filter(a -> statusFilter.equals(a.getStatus())).collect(Collectors.toList());
            }

            if (apps.isEmpty()) {
                Label empty = new Label(isOwnerOrAdmin ? "No applications received yet." : "You haven't applied to any offers yet.");
                empty.getStyleClass().add("oc-empty-label");
                applicationsList.getChildren().add(empty);
                return;
            }

            for (JobApplication a : apps) {
                applicationsList.getChildren().add(createApplicationRow(a));
            }
        } catch (SQLException e) {
            showError("Failed to load applications: " + e.getMessage());
        }
    }

    @FXML private void onAppStatusFilterChanged() { refreshApplications(); }

    private HBox createApplicationRow(JobApplication a) {
        HBox row = new HBox(12);
        row.getStyleClass().add("oc-list-row");
        row.setAlignment(Pos.CENTER_LEFT);
        row.setPadding(new Insets(12, 16, 12, 16));

        Offer offer = offerMap.get(a.getOfferId());
        String offerTitle = offer != null ? offer.getTitle() : "Offer #" + a.getOfferId();

        VBox info = new VBox(2);
        HBox.setHgrow(info, Priority.ALWAYS);
        Label title = new Label(offerTitle);
        title.getStyleClass().add("oc-row-title");
        Label meta = new Label("Applicant: " + getUserName(a.getApplicantId()) +
                (a.getAppliedAt() != null ? " • Applied: " + a.getAppliedAt().toLocalDateTime().format(DateTimeFormatter.ofPattern("MMM dd, yyyy")) : ""));
        meta.getStyleClass().add("oc-row-meta");
        info.getChildren().addAll(title, meta);

        // AI score badge
        VBox scoreBox = new VBox(2);
        scoreBox.setAlignment(Pos.CENTER);
        if (a.getAiScore() != null) {
            Label scoreLbl = new Label(a.getAiScore() + "%");
            scoreLbl.getStyleClass().addAll("oc-score-badge",
                    a.getAiScore() >= 70 ? "oc-score-high" : a.getAiScore() >= 40 ? "oc-score-medium" : "oc-score-low");
            Label scoreLabel = new Label("AI Score");
            scoreLabel.getStyleClass().add("oc-score-label");
            scoreBox.getChildren().addAll(scoreLbl, scoreLabel);
        }

        Label status = new Label(a.getStatus());
        status.getStyleClass().addAll("oc-status-badge", "oc-status-" + a.getStatus().toLowerCase());

        HBox actions = new HBox(6);
        actions.setAlignment(Pos.CENTER);

        if (isOwnerOrAdmin) {
            // Owner can review, accept, reject
            Button btnAiScore = new Button("🎯 AI Score");
            btnAiScore.getStyleClass().add("oc-btn-secondary");
            btnAiScore.setOnAction(e -> runAiScoring(a));

            Button btnAccept = new Button("✓ Accept");
            btnAccept.getStyleClass().add("oc-btn-primary");
            btnAccept.setOnAction(e -> updateApplicationStatus(a, "ACCEPTED"));

            Button btnReject = new Button("✗ Reject");
            btnReject.getStyleClass().add("oc-btn-danger");
            btnReject.setOnAction(e -> updateApplicationStatus(a, "REJECTED"));

            if ("PENDING".equals(a.getStatus()) || "REVIEWED".equals(a.getStatus())) {
                actions.getChildren().addAll(btnAiScore, btnAccept, btnReject);
            }
        } else {
            // Applicant can withdraw
            if ("PENDING".equals(a.getStatus())) {
                Button btnWithdraw = new Button("Withdraw");
                btnWithdraw.getStyleClass().add("oc-btn-danger");
                btnWithdraw.setOnAction(e -> updateApplicationStatus(a, "WITHDRAWN"));
                actions.getChildren().add(btnWithdraw);
            }
        }

        Button btnViewCover = new Button("View");
        btnViewCover.getStyleClass().add("oc-btn-secondary");
        btnViewCover.setOnAction(e -> {
            Alert alert = new Alert(Alert.AlertType.INFORMATION);
            alert.setTitle("Application Details");
            alert.setHeaderText(offerTitle + " — " + getUserName(a.getApplicantId()));
            String content = "Cover Letter:\n" + (a.getCoverLetter() != null ? a.getCoverLetter() : "N/A");
            if (a.getAiFeedback() != null) content += "\n\nAI Feedback:\n" + a.getAiFeedback();
            alert.setContentText(content);
            alert.getDialogPane().setMinWidth(500);
            alert.showAndWait();
        });
        actions.getChildren().add(btnViewCover);

        row.getChildren().addAll(info, scoreBox, status, actions);
        return row;
    }

    private void showApplyDialog(Offer offer) {
        Dialog<JobApplication> dialog = new Dialog<>();
        dialog.setTitle("Apply to: " + offer.getTitle());
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.OK, ButtonType.CANCEL);
        dialog.getDialogPane().setMinWidth(450);

        VBox content = new VBox(10);
        content.setPadding(new Insets(16));
        Label lbl = new Label("Write your cover letter / motivation:");
        TextArea taCover = new TextArea();
        taCover.setPromptText("Why are you a great fit for this offer?");
        taCover.setPrefRowCount(8);
        content.getChildren().addAll(lbl, taCover);
        dialog.getDialogPane().setContent(content);

        dialog.setResultConverter(btn -> {
            if (btn == ButtonType.OK) {
                JobApplication a = new JobApplication(
                        offer.getId(), currentUser.getId(), taCover.getText().trim(), "PENDING"
                );
                return a;
            }
            return null;
        });

        dialog.showAndWait().ifPresent(a -> {
            try {
                serviceApp.ajouter(a);
                showInfo("Application submitted!");
                SoundManager.getInstance().play(SoundManager.MESSAGE_SENT);
            } catch (SQLException e) {
                showError("Application failed: " + e.getMessage());
            }
        });
    }

    private void updateApplicationStatus(JobApplication a, String newStatus) {
        try {
            a.setStatus(newStatus);
            serviceApp.modifier(a);
            SoundManager.getInstance().play(SoundManager.MESSAGE_SENT);

            // If accepted, auto-create a draft contract + send email + notify
            if ("ACCEPTED".equals(newStatus)) {
                Offer offer = offerMap.get(a.getOfferId());
                if (offer != null) {
                    Contract c = new Contract(
                            offer.getId(), a.getApplicantId(), offer.getOwnerId(),
                            null, offer.getAmount(), offer.getCurrency(),
                            "DRAFT", offer.getStartDate(), offer.getEndDate()
                    );
                    // Generate blockchain hash
                    String hash = BlockchainVerifier.generateHash(0, "Auto-generated from application acceptance", offer.getAmount());
                    c.setBlockchainHash(hash);
                    try {
                        serviceContract.ajouter(c);
                    } catch (Exception contractEx) {
                        // Rollback application status on contract creation failure
                        a.setStatus("REVIEWED");
                        try { serviceApp.modifier(a); } catch (Exception ignored) {}
                        showError("Failed to create contract: " + contractEx.getMessage());
                        refreshApplications();
                        return;
                    }

                    String applicantName = getUserName(a.getApplicantId());
                    String ownerName = getUserName(offer.getOwnerId());

                    // Generate PDF + send email + notify in background
                    new Thread(() -> {
                        try {
                            // 1) Generate contract PDF
                            File pdf = ContractPdfGenerator.generatePdf(c, offer.getTitle(), ownerName, applicantName);

                            // 2) Send email with PDF to applicant
                            User applicant = serviceUser.getById(a.getApplicantId());
                            if (applicant != null && applicant.getEmail() != null) {
                                EmailService.sendContractEmail(
                                        applicant.getEmail(), applicant.getFirstName(),
                                        ownerName, offer.getTitle(),
                                        offer.getCurrency(), offer.getAmount(),
                                        hash, pdf
                                );
                            }

                            // 3) Create in-app notification for applicant
                            serviceNotification.notifyContractReady(
                                    a.getApplicantId(), applicantName, offer.getTitle(), c.getId());

                            Platform.runLater(() -> showInfo(
                                    "Application accepted! Contract created, email sent & applicant notified."));
                        } catch (Exception ex) {
                            ex.printStackTrace();
                            Platform.runLater(() -> showInfo(
                                    "Contract created but email sending failed: " + ex.getMessage()));
                        }
                    }).start();
                }
            }
            refreshApplications();
        } catch (SQLException e) {
            showError("Status update failed: " + e.getMessage());
        }
    }

    private void runAiScoring(JobApplication a) {
        Offer offer = offerMap.get(a.getOfferId());
        if (offer == null) { showError("Offer not found."); return; }

        aiStatusLabel.setText("🤖 Scoring applicant...");
        new Thread(() -> {
            String result = zaiService.scoreApplicant(
                    offer.getTitle(),
                    offer.getRequiredSkills() != null ? offer.getRequiredSkills() : "",
                    a.getCoverLetter() != null ? a.getCoverLetter() : "",
                    getUserName(a.getApplicantId())
            );

            Platform.runLater(() -> {
                aiStatusLabel.setText("");
                try {
                    JsonObject json = JsonParser.parseString(result).getAsJsonObject();
                    int score = json.get("score").getAsInt();
                    a.setAiScore(score);
                    a.setAiFeedback(result);
                    a.setStatus("REVIEWED");
                    serviceApp.modifier(a);
                    showInfo("AI Score: " + score + "/100");
                    refreshApplications();
                } catch (Exception ex) {
                    // Couldn't parse JSON, store raw feedback
                    a.setAiFeedback(result);
                    try { serviceApp.modifier(a); } catch (Exception ignored) {}
                    showInfo("AI feedback received (raw):\n" + result.substring(0, Math.min(200, result.length())));
                }
            });
        }).start();
    }

    // ================================================================
    // TAB 4: CONTRACTS TABLE
    // ================================================================

    private void setupContractsTable() {
        colContractId.setCellValueFactory(new PropertyValueFactory<>("id"));
        colContractAmount.setCellValueFactory(new PropertyValueFactory<>("amount"));
        colContractStatus.setCellValueFactory(new PropertyValueFactory<>("status"));

        // Custom cell factories for rich display
        colContractOffer.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setText(null); return; }
                Contract c = getTableRow().getItem();
                Offer o = offerMap.get(c.getOfferId());
                setText(o != null ? o.getTitle() : "Offer #" + c.getOfferId());
            }
        });
        colContractParty.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setText(null); return; }
                Contract c = getTableRow().getItem();
                setText(getUserName(isOwnerOrAdmin ? c.getApplicantId() : c.getOwnerId()));
            }
        });
        colContractAmount.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setText(null); return; }
                Contract c = getTableRow().getItem();
                setText(String.format("%s %.2f", c.getCurrency(), c.getAmount()));
            }
        });
        colContractRisk.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setText(null); setStyle(""); return; }
                Contract c = getTableRow().getItem();
                if (c.getRiskScore() != null) {
                    setText(c.getRiskScore() + "%");
                    if (c.getRiskScore() <= 30) setStyle("-fx-text-fill: #22c55e;");
                    else if (c.getRiskScore() <= 70) setStyle("-fx-text-fill: #f59e0b;");
                    else setStyle("-fx-text-fill: #ef4444;");
                } else {
                    setText("—");
                    setStyle("");
                }
            }
        });
        colContractDates.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setText(null); return; }
                Contract c = getTableRow().getItem();
                String s = (c.getStartDate() != null ? c.getStartDate().toString() : "?") + " → " + (c.getEndDate() != null ? c.getEndDate().toString() : "?");
                setText(s);
            }
        });
        colContractStatus.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setText(null); setGraphic(null); return; }
                Contract c = getTableRow().getItem();
                Label lbl = new Label(c.getStatus());
                lbl.getStyleClass().addAll("oc-status-badge", "oc-status-" + c.getStatus().toLowerCase().replace("_", "-"));
                setGraphic(lbl);
                setText(null);
            }
        });
        colContractActions.setCellFactory(col -> new TableCell<>() {
            @Override protected void updateItem(String item, boolean empty) {
                super.updateItem(item, empty);
                if (empty || getTableRow() == null || getTableRow().getItem() == null) { setGraphic(null); return; }
                Contract c = getTableRow().getItem();
                HBox box = new HBox(4);
                box.setAlignment(Pos.CENTER);

                Button btnPdf = new Button("PDF");
                btnPdf.getStyleClass().add("oc-btn-secondary");
                btnPdf.setOnAction(e -> exportContractPdf(c));

                Button btnRisk = new Button("⚠ Risk");
                btnRisk.getStyleClass().add("oc-btn-secondary");
                btnRisk.setOnAction(e -> runRiskAnalysis(c));

                Button btnChat = new Button("💬");
                btnChat.getStyleClass().add("oc-btn-secondary");
                btnChat.setOnAction(e -> {
                    selectedAiContract = c;
                    aiChatHistory.clear();
                    // Switch to AI tab
                    for (Node n : tabBar.getChildren()) {
                        if (n instanceof Button && ((Button) n).getText().contains("AI")) {
                            switchTab((Button) n, "AI Assistant");
                            addAiMessage("system", "Selected contract #" + c.getId() + ". Ask me anything about it!");
                            break;
                        }
                    }
                });

                box.getChildren().addAll(btnPdf, btnRisk, btnChat);

                // QR Verification button for HR/owner
                if (isOwnerOrAdmin && c.getBlockchainHash() != null && !c.getBlockchainHash().isEmpty()) {
                    Button btnVerify = new Button("🔍 Verify");
                    btnVerify.getStyleClass().add("oc-btn-secondary");
                    btnVerify.setOnAction(e -> showQrVerificationDialog(c));
                    box.getChildren().add(btnVerify);
                }

                if (isOwnerOrAdmin && "DRAFT".equals(c.getStatus())) {
                    Button btnActivate = new Button("Activate");
                    btnActivate.getStyleClass().add("oc-btn-primary");
                    btnActivate.setOnAction(e -> {
                        try {
                            c.setStatus("ACTIVE");
                            serviceContract.modifier(c);
                            refreshContracts();
                        } catch (SQLException ex) { showError(ex.getMessage()); }
                    });
                    box.getChildren().add(0, btnActivate);
                }

                setGraphic(box);
                setText(null);
            }
        });
    }

    private void refreshContracts() {
        try {
            List<Contract> contracts;
            if (isOwnerOrAdmin) {
                contracts = serviceContract.getByOwner(currentUser.getId());
            } else {
                contracts = serviceContract.getByApplicant(currentUser.getId());
            }

            String statusFilter = filterContractStatus.getValue();
            if (!"All".equals(statusFilter)) {
                contracts = contracts.stream().filter(c -> statusFilter.equals(c.getStatus())).collect(Collectors.toList());
            }

            contractsTable.setItems(FXCollections.observableArrayList(contracts));
        } catch (SQLException e) {
            showError("Failed to load contracts: " + e.getMessage());
        }
    }

    @FXML private void onContractStatusFilterChanged() { refreshContracts(); }

    // ================================================================
    // QR CODE VERIFICATION DIALOG (HR)
    // ================================================================

    private void showQrVerificationDialog(Contract contract) {
        Dialog<ButtonType> dialog = new Dialog<>();
        dialog.setTitle("Contract QR Verification");
        dialog.getDialogPane().getButtonTypes().addAll(ButtonType.CLOSE);
        dialog.getDialogPane().setMinWidth(550);
        dialog.getDialogPane().setMinHeight(500);

        VBox root = new VBox(16);
        root.setPadding(new Insets(20));
        root.setAlignment(Pos.TOP_CENTER);

        // Title
        Label titleLbl = new Label("🔐 Blockchain Contract Verification");
        titleLbl.setStyle("-fx-font-size: 18px; -fx-font-weight: bold; -fx-text-fill: #2C666E;");

        Offer offer = offerMap.get(contract.getOfferId());
        String offerTitle = offer != null ? offer.getTitle() : "Contract #" + contract.getId();
        Label offerLbl = new Label("Offer: " + offerTitle);
        offerLbl.setStyle("-fx-font-size: 13px; -fx-text-fill: #9E9EA8;");

        // QR Code display
        VBox qrBox = new VBox(8);
        qrBox.setAlignment(Pos.CENTER);
        qrBox.setStyle("-fx-background-color: #14131A; -fx-background-radius: 12; -fx-padding: 20;");

        Label qrTitle = new Label("BLOCKCHAIN QR CODE");
        qrTitle.setStyle("-fx-font-size: 11px; -fx-text-fill: #90DDF0; -fx-font-weight: bold;");

        // Load QR image
        javafx.scene.image.ImageView qrImageView = new javafx.scene.image.ImageView();
        qrImageView.setFitWidth(180);
        qrImageView.setFitHeight(180);
        qrImageView.setPreserveRatio(true);

        new Thread(() -> {
            try {
                byte[] qrBytes = ContractPdfGenerator.fetchQrCode(contract.getBlockchainHash());
                if (qrBytes != null) {
                    javafx.scene.image.Image img = new javafx.scene.image.Image(new java.io.ByteArrayInputStream(qrBytes));
                    Platform.runLater(() -> qrImageView.setImage(img));
                }
            } catch (Exception ignored) {}
        }).start();

        qrBox.getChildren().addAll(qrTitle, qrImageView);

        // Hash display
        String hash = contract.getBlockchainHash();
        Label hashLabel = new Label("SHA-256 Hash:");
        hashLabel.setStyle("-fx-font-size: 10px; -fx-text-fill: #6B6B78; -fx-font-weight: bold;");

        TextField hashField = new TextField(hash);
        hashField.setEditable(false);
        hashField.setStyle("-fx-font-family: 'Courier New'; -fx-font-size: 10px; -fx-background-color: #1C1B22; -fx-text-fill: #90DDF0; -fx-border-color: #2C666E; -fx-border-radius: 6; -fx-background-radius: 6;");

        // Verification input
        Label verifyLabel = new Label("Enter or paste hash to verify:");
        verifyLabel.setStyle("-fx-font-size: 12px; -fx-text-fill: #9E9EA8;");

        TextField verifyInput = new TextField();
        verifyInput.setPromptText("Paste the blockchain hash from the contract PDF...");
        verifyInput.setStyle("-fx-font-family: 'Courier New'; -fx-font-size: 10px; -fx-background-color: #0F0E11; -fx-text-fill: #F0EDEE; -fx-border-color: #2C666E; -fx-border-radius: 6; -fx-background-radius: 6;");

        // Result area
        Label resultLabel = new Label();
        resultLabel.setWrapText(true);
        resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10;");

        // Buttons
        HBox btnRow = new HBox(12);
        btnRow.setAlignment(Pos.CENTER);

        Button btnVerify = new Button("✅ Verify Hash");
        btnVerify.setStyle("-fx-background-color: linear-gradient(to right, #07393C, #2C666E); -fx-text-fill: #F0EDEE; -fx-font-weight: bold; -fx-padding: 10 24; -fx-background-radius: 8; -fx-cursor: hand;");

        Button btnAcceptContract = new Button("✔ Accept & Activate");
        btnAcceptContract.setStyle("-fx-background-color: linear-gradient(to right, #166534, #22c55e); -fx-text-fill: white; -fx-font-weight: bold; -fx-padding: 10 24; -fx-background-radius: 8; -fx-cursor: hand;");
        btnAcceptContract.setVisible(false);

        Button btnReject = new Button("✖ Reject");
        btnReject.setStyle("-fx-background-color: #dc2626; -fx-text-fill: white; -fx-font-weight: bold; -fx-padding: 10 24; -fx-background-radius: 8; -fx-cursor: hand;");
        btnReject.setVisible(false);

        btnVerify.setOnAction(e -> {
            String inputHash = verifyInput.getText().trim();
            if (inputHash.isEmpty()) {
                resultLabel.setText("⚠️ Please enter a hash to verify.");
                resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10; -fx-text-fill: #f59e0b;");
                return;
            }

            boolean formatValid = BlockchainVerifier.isValidHash(inputHash);
            boolean matches = inputHash.equalsIgnoreCase(contract.getBlockchainHash());

            if (matches) {
                resultLabel.setText("✅ VERIFIED — Hash matches! Contract is authentic and untampered.");
                resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10; -fx-text-fill: #22c55e; -fx-font-weight: bold;");
                btnAcceptContract.setVisible(true);
                btnReject.setVisible(true);
                SoundManager.getInstance().play(SoundManager.MESSAGE_SENT);
            } else if (formatValid) {
                resultLabel.setText("❌ MISMATCH — Hash is valid format but does NOT match this contract. Possible tampering!");
                resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10; -fx-text-fill: #ef4444; -fx-font-weight: bold;");
                btnAcceptContract.setVisible(false);
                btnReject.setVisible(true);
                SoundManager.getInstance().play(SoundManager.ERROR);
            } else {
                resultLabel.setText("❌ INVALID — Not a valid SHA-256 hash format.");
                resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10; -fx-text-fill: #ef4444;");
                SoundManager.getInstance().play(SoundManager.ERROR);
            }
        });

        btnAcceptContract.setOnAction(e -> {
            try {
                contract.setStatus("PENDING_SIGNATURE");
                contract.setSignedAt(new java.sql.Timestamp(System.currentTimeMillis()));
                serviceContract.modifier(contract);

                // Notify applicant
                String applicantName = getUserName(contract.getApplicantId());
                serviceNotification.notifyContractVerified(
                        currentUser.getId(), applicantName, offerTitle, contract.getId());

                resultLabel.setText("✅ Contract verified and moved to PENDING_SIGNATURE!");
                resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10; -fx-text-fill: #22c55e; -fx-font-weight: bold;");
                btnAcceptContract.setDisable(true);
                btnReject.setDisable(true);
                refreshContracts();
                SoundManager.getInstance().play(SoundManager.MESSAGE_SENT);
            } catch (SQLException ex) {
                showError("Failed to update contract: " + ex.getMessage());
            }
        });

        btnReject.setOnAction(e -> {
            try {
                contract.setStatus("DISPUTED");
                serviceContract.modifier(contract);
                resultLabel.setText("⚠️ Contract marked as DISPUTED.");
                resultLabel.setStyle("-fx-font-size: 14px; -fx-padding: 10; -fx-text-fill: #f59e0b; -fx-font-weight: bold;");
                btnAcceptContract.setDisable(true);
                btnReject.setDisable(true);
                refreshContracts();
                SoundManager.getInstance().play(SoundManager.ERROR);
            } catch (SQLException ex) {
                showError("Failed to update contract: " + ex.getMessage());
            }
        });

        btnRow.getChildren().addAll(btnVerify, btnAcceptContract, btnReject);

        root.getChildren().addAll(titleLbl, offerLbl, qrBox, hashLabel, hashField, verifyLabel, verifyInput, btnRow, resultLabel);

        ScrollPane scroll = new ScrollPane(root);
        scroll.setFitToWidth(true);
        scroll.setStyle("-fx-background: #0A090C; -fx-background-color: #0A090C;");
        dialog.getDialogPane().setContent(scroll);
        dialog.showAndWait();
    }


    private void exportContractPdf(Contract c) {
        Offer offer = offerMap.get(c.getOfferId());
        String offerTitle = offer != null ? offer.getTitle() : "Offer #" + c.getOfferId();
        String ownerName = getUserName(c.getOwnerId());
        String applicantName = getUserName(c.getApplicantId());

        new Thread(() -> {
            try {
                File pdf = ContractPdfGenerator.generatePdf(c, offerTitle, ownerName, applicantName);
                Platform.runLater(() -> {
                    // Offer to save
                    FileChooser fc = new FileChooser();
                    fc.setTitle("Save Contract PDF");
                    fc.setInitialFileName("contract_" + c.getId() + ".pdf");
                    fc.getExtensionFilters().add(new FileChooser.ExtensionFilter("PDF Files", "*.pdf"));
                    File target = fc.showSaveDialog(rootPane.getScene().getWindow());
                    if (target != null) {
                        try {
                            java.nio.file.Files.copy(pdf.toPath(), target.toPath(), java.nio.file.StandardCopyOption.REPLACE_EXISTING);
                            showInfo("PDF exported to: " + target.getName());
                            if (Desktop.isDesktopSupported()) {
                                Desktop.getDesktop().open(target);
                            }
                        } catch (Exception ex) {
                            showError("Export failed: " + ex.getMessage());
                        }
                    }
                });
            } catch (Exception ex) {
                Platform.runLater(() -> showError("PDF generation failed: " + ex.getMessage()));
            }
        }).start();
    }

    private void runRiskAnalysis(Contract c) {
        aiStatusLabel.setText("🤖 Analyzing risk...");
        new Thread(() -> {
            String terms = c.getTerms() != null ? c.getTerms() : "No terms specified";
            String duration = (c.getStartDate() != null && c.getEndDate() != null)
                    ? c.getStartDate() + " to " + c.getEndDate() : "Unknown";
            String result = zaiService.analyzeRisk(terms, c.getAmount(), duration);

            Platform.runLater(() -> {
                aiStatusLabel.setText("");
                try {
                    JsonObject json = JsonParser.parseString(result).getAsJsonObject();
                    int riskScore = json.get("risk_score").getAsInt();
                    c.setRiskScore(riskScore);
                    c.setRiskFactors(result);
                    serviceContract.modifier(c);
                    refreshContracts();
                    showInfo("Risk Score: " + riskScore + "/100 (" +
                            (riskScore <= 30 ? "Low" : riskScore <= 70 ? "Medium" : "High") + ")");
                } catch (Exception ex) {
                    showInfo("Risk analysis:\n" + result.substring(0, Math.min(300, result.length())));
                }
            });
        }).start();
    }

    // ================================================================
    // TAB 5: AI ASSISTANT
    // ================================================================

    @FXML
    private void aiSendMessage() {
        String msg = aiInputField.getText().trim();
        if (msg.isEmpty()) return;
        aiInputField.clear();
        addAiMessage("user", msg);

        aiStatusLabel.setText("🤖 Thinking...");
        new Thread(() -> {
            String response;
            if (selectedAiContract != null && selectedAiContract.getTerms() != null) {
                response = zaiService.chatWithContract(selectedAiContract.getTerms(), aiChatHistory, msg);
            } else {
                response = zaiService.chat("You are the SynergyGig AI assistant. Help with offers, applications, and contracts.", msg);
            }
            Map<String, String> userMsg = new HashMap<>();
            userMsg.put("role", "user");
            userMsg.put("content", msg);
            aiChatHistory.add(userMsg);
            Map<String, String> aiMsg = new HashMap<>();
            aiMsg.put("role", "assistant");
            aiMsg.put("content", response);
            aiChatHistory.add(aiMsg);
            Platform.runLater(() -> {
                aiStatusLabel.setText("");
                addAiMessage("assistant", response);
            });
        }).start();
    }

    @FXML private void aiGenerateContract() {
        if (selectedAiContract == null) {
            showError("Select a contract first (from Contracts tab → 💬 button).");
            return;
        }
        Offer offer = offerMap.get(selectedAiContract.getOfferId());
        if (offer == null) { showError("Offer not found."); return; }

        addAiMessage("user", "Generate contract terms for: " + offer.getTitle());
        aiStatusLabel.setText("🤖 Generating contract...");

        new Thread(() -> {
            String result = zaiService.generateContract(
                    offer.getTitle(), offer.getDescription() != null ? offer.getDescription() : "",
                    offer.getAmount(), getUserName(selectedAiContract.getApplicantId()),
                    offer.getStartDate() != null ? offer.getStartDate().toString() : "TBD",
                    offer.getEndDate() != null ? offer.getEndDate().toString() : "TBD"
            );
            Platform.runLater(() -> {
                aiStatusLabel.setText("");
                addAiMessage("assistant", result);
                // Ask if they want to apply the terms
                Alert confirm = new Alert(Alert.AlertType.CONFIRMATION, "Apply these generated terms to the contract?", ButtonType.YES, ButtonType.NO);
                confirm.showAndWait().ifPresent(btn -> {
                    if (btn == ButtonType.YES) {
                        try {
                            selectedAiContract.setTerms(result);
                            String hash = BlockchainVerifier.generateHash(selectedAiContract.getId(), result, selectedAiContract.getAmount());
                            selectedAiContract.setBlockchainHash(hash);
                            serviceContract.modifier(selectedAiContract);
                            showInfo("Contract terms applied + blockchain hash generated!");
                            refreshContracts();
                        } catch (SQLException e) { showError(e.getMessage()); }
                    }
                });
            });
        }).start();
    }

    @FXML private void aiAnalyzeRisk() {
        if (selectedAiContract == null) { showError("Select a contract first."); return; }
        runRiskAnalysis(selectedAiContract);
    }

    @FXML private void aiImproveTerms() {
        if (selectedAiContract == null || selectedAiContract.getTerms() == null) {
            showError("Select a contract with terms first.");
            return;
        }
        addAiMessage("user", "Improve the contract terms");
        aiStatusLabel.setText("🤖 Improving terms...");
        new Thread(() -> {
            String result = zaiService.improveTerms(selectedAiContract.getTerms());
            Platform.runLater(() -> {
                aiStatusLabel.setText("");
                addAiMessage("assistant", result);
            });
        }).start();
    }

    @FXML private void aiSummarize() {
        if (selectedAiContract == null || selectedAiContract.getTerms() == null) {
            showError("Select a contract with terms first.");
            return;
        }
        addAiMessage("user", "Summarize this contract");
        aiStatusLabel.setText("🤖 Summarizing...");
        new Thread(() -> {
            String result = zaiService.summarizeContract(selectedAiContract.getTerms());
            Platform.runLater(() -> {
                aiStatusLabel.setText("");
                addAiMessage("assistant", result);
            });
        }).start();
    }

    @FXML private void aiDraftEmail() {
        if (selectedAiContract == null) { showError("Select a contract first."); return; }
        // Show dialog to choose email type and style
        ChoiceDialog<String> typeDialog = new ChoiceDialog<>("CONTRACT_READY", "ACCEPTED", "REJECTED", "CONTRACT_READY");
        typeDialog.setTitle("Draft Email");
        typeDialog.setHeaderText("What type of email?");
        typeDialog.showAndWait().ifPresent(type -> {
            ChoiceDialog<String> styleDialog = new ChoiceDialog<>("Professional", "Professional", "Friendly", "Direct");
            styleDialog.setTitle("Email Style");
            styleDialog.setHeaderText("Choose tone:");
            styleDialog.showAndWait().ifPresent(style -> {
                Offer offer = offerMap.get(selectedAiContract.getOfferId());
                addAiMessage("user", "Draft a " + type + " email (" + style + " tone)");
                aiStatusLabel.setText("🤖 Drafting email...");
                new Thread(() -> {
                    String result = zaiService.draftEmail(type, style,
                            getUserName(selectedAiContract.getApplicantId()),
                            offer != null ? offer.getTitle() : "Contract #" + selectedAiContract.getId(),
                            "Contract amount: " + selectedAiContract.getCurrency() + " " + selectedAiContract.getAmount());
                    Platform.runLater(() -> {
                        aiStatusLabel.setText("");
                        addAiMessage("assistant", result);
                    });
                }).start();
            });
        });
    }

    @FXML private void aiScoreApplicant() {
        // Let user pick an application to score
        try {
            List<JobApplication> apps = serviceApp.recuperer();
            List<String> choices = apps.stream()
                    .map(a -> "#" + a.getId() + " — " + getUserName(a.getApplicantId()) + " → Offer #" + a.getOfferId())
                    .collect(Collectors.toList());
            if (choices.isEmpty()) { showError("No applications found."); return; }
            ChoiceDialog<String> d = new ChoiceDialog<>(choices.get(0), choices);
            d.setTitle("Score Applicant");
            d.setHeaderText("Select an application to score:");
            d.showAndWait().ifPresent(choice -> {
                int appId = Integer.parseInt(choice.split(" — ")[0].replace("#", ""));
                apps.stream().filter(a -> a.getId() == appId).findFirst().ifPresent(this::runAiScoring);
            });
        } catch (SQLException e) { showError(e.getMessage()); }
    }

    @FXML private void aiOfferStrategy() {
        try {
            List<Offer> offers = serviceOffer.getByOwner(currentUser.getId());
            if (offers.isEmpty()) { showError("No offers found."); return; }
            List<String> choices = offers.stream()
                    .map(o -> "#" + o.getId() + " — " + o.getTitle())
                    .collect(Collectors.toList());
            ChoiceDialog<String> d = new ChoiceDialog<>(choices.get(0), choices);
            d.setTitle("Offer Strategy");
            d.setHeaderText("Select an offer to analyze:");
            d.showAndWait().ifPresent(choice -> {
                int offerId = Integer.parseInt(choice.split(" — ")[0].replace("#", ""));
                offers.stream().filter(o -> o.getId() == offerId).findFirst().ifPresent(offer -> {
                    addAiMessage("user", "Analyze strategy for: " + offer.getTitle());
                    aiStatusLabel.setText("🤖 Analyzing strategy...");
                    new Thread(() -> {
                        String result = zaiService.adviseOfferStrategy(offer.getTitle(),
                                offer.getDescription() != null ? offer.getDescription() : "",
                                offer.getAmount(),
                                offer.getLocation() != null ? offer.getLocation() : "Remote",
                                offer.getRequiredSkills() != null ? offer.getRequiredSkills() : "");
                        Platform.runLater(() -> {
                            aiStatusLabel.setText("");
                            addAiMessage("assistant", result);
                        });
                    }).start();
                });
            });
        } catch (SQLException e) { showError(e.getMessage()); }
    }

    @FXML private void aiEnhanceDescription() {
        TextInputDialog dialog = new TextInputDialog();
        dialog.setTitle("Enhance Description");
        dialog.setHeaderText("Enter bullet points for the offer description:");
        dialog.getEditor().setPrefWidth(400);
        dialog.showAndWait().ifPresent(bullets -> {
            TextInputDialog titleDialog = new TextInputDialog();
            titleDialog.setTitle("Offer Title");
            titleDialog.setHeaderText("What's the offer title?");
            titleDialog.showAndWait().ifPresent(title -> {
                addAiMessage("user", "Enhance description for: " + title);
                aiStatusLabel.setText("🤖 Enhancing...");
                new Thread(() -> {
                    String result = zaiService.enhanceOfferDescription(bullets, title);
                    Platform.runLater(() -> {
                        aiStatusLabel.setText("");
                        addAiMessage("assistant", result);
                    });
                }).start();
            });
        });
    }

    // ==================== AI Chat UI Helpers ====================

    private void addAiMessage(String role, String text) {
        HBox row = new HBox(8);
        row.setPadding(new Insets(6, 8, 6, 8));

        Label bubble = new Label(text);
        bubble.setWrapText(true);
        bubble.setMaxWidth(500);
        bubble.setPadding(new Insets(10, 14, 10, 14));

        if ("user".equals(role)) {
            bubble.getStyleClass().add("oc-chat-user");
            row.setAlignment(Pos.CENTER_RIGHT);
        } else if ("assistant".equals(role)) {
            bubble.getStyleClass().add("oc-chat-ai");
            row.setAlignment(Pos.CENTER_LEFT);
        } else {
            bubble.getStyleClass().add("oc-chat-system");
            row.setAlignment(Pos.CENTER);
        }

        row.getChildren().add(bubble);
        aiChatBox.getChildren().add(row);

        // Auto scroll to bottom
        Platform.runLater(() -> aiChatScroll.setVvalue(1.0));
    }

    // ==================== Utility ====================

    private void showInfo(String msg) {
        Alert alert = new Alert(Alert.AlertType.INFORMATION, msg);
        alert.setHeaderText(null);
        alert.showAndWait();
    }

    private void showError(String msg) {
        Alert alert = new Alert(Alert.AlertType.ERROR, msg);
        alert.setHeaderText("Error");
        alert.showAndWait();
    }
}
