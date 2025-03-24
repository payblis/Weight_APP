<?php
require_once 'includes/config.php';
require_once 'includes/chatgpt.php';

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupération des données de l'utilisateur
$userId = $_SESSION['user_id'];

try {
    // Récupération des suggestions récentes
    $suggestionsStmt = $pdo->prepare("
        SELECT *
        FROM ai_suggestions
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $suggestionsStmt->execute([$userId]);
    $suggestions = $suggestionsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques des suggestions
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_suggestions,
            COUNT(DISTINCT DATE(created_at)) as active_days,
            COUNT(CASE WHEN implemented = 1 THEN 1 END) as implemented_suggestions
        FROM ai_suggestions
        WHERE user_id = ?
        AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $statsStmt->execute([$userId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des données : " . $e->getMessage());
    $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Une erreur est survenue'];
}

include 'components/user_header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Coach IA</h1>
        <button class="btn btn-primary" onclick="askQuestion()">
            <i class="fas fa-question-circle me-2"></i>Poser une question
        </button>
    </div>

    <!-- Statistiques -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Suggestions reçues</div>
                    <div class="stat-value"><?php echo $stats['total_suggestions']; ?></div>
                    <div class="stat-text">Ces 30 derniers jours</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Suggestions appliquées</div>
                    <div class="stat-value"><?php echo $stats['implemented_suggestions']; ?></div>
                    <div class="stat-text">Sur la même période</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-12 mb-4">
            <div class="stat-card">
                <div class="card-body">
                    <div class="stat-label">Jours d'interaction</div>
                    <div class="stat-value"><?php echo $stats['active_days']; ?>/30</div>
                    <div class="stat-text">Derniers jours</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Suggestions récentes -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h5 class="m-0 font-weight-bold text-primary">Suggestions récentes</h5>
        </div>
        <div class="card-body">
            <?php if (empty($suggestions)): ?>
                <p class="text-muted">Aucune suggestion pour le moment.</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($suggestions as $suggestion): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker">
                                <i class="fas fa-robot"></i>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-2"><?php echo htmlspecialchars($suggestion['title']); ?></h6>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($suggestion['content'])); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($suggestion['created_at'])); ?>
                                    </small>
                                    <?php if (!$suggestion['implemented']): ?>
                                        <button class="btn btn-sm btn-outline-success"
                                                onclick="markAsImplemented(<?php echo $suggestion['id']; ?>)">
                                            <i class="fas fa-check me-1"></i>Marquer comme appliqué
                                        </button>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check me-1"></i>Appliqué
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de question -->
<div class="modal fade" id="questionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Poser une question à votre coach</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="questionForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Votre question</label>
                        <textarea class="form-control" name="question" rows="3" required
                                placeholder="Ex: Comment puis-je améliorer mon alimentation ?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de conseil</label>
                        <select class="form-select" name="type" required>
                            <?php foreach (AI_CONVERSATION_TYPES as $type => $label): ?>
                                <option value="<?php echo $type; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Envoyer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let questionModal;

document.addEventListener('DOMContentLoaded', function() {
    questionModal = new bootstrap.Modal(document.getElementById('questionModal'));
    
    // Gestionnaire du formulaire de question
    document.getElementById('questionForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        const spinner = submitBtn.querySelector('.spinner-border');
        const originalText = submitBtn.innerHTML;
        
        // Désactiver le bouton et afficher le spinner
        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        submitBtn.innerHTML = spinner.outerHTML + ' Envoi en cours...';
        
        const formData = new FormData(form);
        
        try {
            const response = await fetch('/api/ask_ai.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Succès', 'Votre question a été envoyée', 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showToast('Erreur', 'Une erreur est survenue', 'error');
        } finally {
            // Réactiver le bouton et cacher le spinner
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            questionModal.hide();
        }
    });
});

function askQuestion() {
    questionModal.show();
}

async function markAsImplemented(id) {
    try {
        const response = await fetch('/api/mark_suggestion_implemented.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Succès', 'Suggestion marquée comme appliquée', 'success');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showToast('Erreur', data.message || 'Une erreur est survenue', 'error');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showToast('Erreur', 'Une erreur est survenue', 'error');
    }
}
</script>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    position: relative;
    padding-left: 40px;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
}

.timeline-marker i {
    color: #6c757d;
}

.timeline-content {
    background: #fff;
    padding: 15px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: 15px;
    top: 30px;
    height: calc(100% + 10px);
    width: 2px;
    background: #e9ecef;
}
</style>

<?php include 'components/user_footer.php'; ?> 