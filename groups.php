<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Gérer les actions sur les groupes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                if (isset($_POST['name']) && isset($_POST['description'])) {
                    $group_id = createGroup($user_id, $_POST['name'], $_POST['description']);
                    if ($group_id) {
                        $_SESSION['success_message'] = "Le groupe a été créé avec succès !";
                    } else {
                        $_SESSION['error_message'] = "Une erreur est survenue lors de la création du groupe.";
                    }
                }
                break;
                
            case 'join':
                if (isset($_POST['group_id'])) {
                    if (addGroupMember($_POST['group_id'], $user_id)) {
                        $_SESSION['success_message'] = "Vous avez rejoint le groupe avec succès !";
                    } else {
                        $_SESSION['error_message'] = "Une erreur est survenue lors de l'adhésion au groupe.";
                    }
                }
                break;
                
            case 'leave':
                if (isset($_POST['group_id'])) {
                    if (removeGroupMember($_POST['group_id'], $user_id)) {
                        $_SESSION['success_message'] = "Vous avez quitté le groupe avec succès !";
                    } else {
                        $_SESSION['error_message'] = "Une erreur est survenue lors du départ du groupe.";
                    }
                }
                break;
                
            case 'add_member':
                if (isset($_POST['group_id']) && isset($_POST['user_id']) && isGroupAdmin($_POST['group_id'], $user_id)) {
                    if (addGroupMember($_POST['group_id'], $_POST['user_id'])) {
                        $_SESSION['success_message'] = "Le membre a été ajouté avec succès !";
                    } else {
                        $_SESSION['error_message'] = "Une erreur est survenue lors de l'ajout du membre.";
                    }
                }
                break;
                
            case 'remove_member':
                if (isset($_POST['group_id']) && isset($_POST['user_id']) && isGroupAdmin($_POST['group_id'], $user_id)) {
                    if (removeGroupMember($_POST['group_id'], $_POST['user_id'])) {
                        $_SESSION['success_message'] = "Le membre a été retiré avec succès !";
                    } else {
                        $_SESSION['error_message'] = "Une erreur est survenue lors du retrait du membre.";
                    }
                }
                break;
        }
        redirect('groups.php');
    }
}

// Récupérer les groupes de l'utilisateur
$user_groups = getUserGroups($user_id);

// Récupérer tous les groupes publics
$sql = "SELECT g.*, u.username as creator_name, 
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM community_groups g
        JOIN users u ON g.created_by = u.id
        WHERE g.id NOT IN (SELECT group_id FROM group_members WHERE user_id = ?)
        ORDER BY member_count DESC";
$available_groups = fetchAll($sql, [$user_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Groupes - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Mes groupes -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Mes groupes</h5>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                            <i class="fas fa-plus me-1"></i>Créer un groupe
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_groups)): ?>
                            <p class="text-muted mb-0">Vous n'êtes membre d'aucun groupe.</p>
                        <?php else: ?>
                            <?php foreach ($user_groups as $group): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($group['name']); ?></h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($group['description']); ?></p>
                                                <small class="text-muted">
                                                    Créé par <?php echo htmlspecialchars($group['creator_name']); ?> • 
                                                    <?php echo $group['member_count']; ?> membres
                                                </small>
                                            </div>
                                            <div>
                                                <?php if ($group['role'] === 'admin'): ?>
                                                    <span class="badge bg-primary">Admin</span>
                                                <?php endif; ?>
                                                <a href="group.php?id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                                    <i class="fas fa-eye me-1"></i>Voir
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Groupes disponibles -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Groupes disponibles</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($available_groups)): ?>
                            <p class="text-muted mb-0">Aucun groupe disponible.</p>
                        <?php else: ?>
                            <?php foreach ($available_groups as $group): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($group['name']); ?></h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($group['description']); ?></p>
                                                <small class="text-muted">
                                                    Créé par <?php echo htmlspecialchars($group['creator_name']); ?> • 
                                                    <?php echo $group['member_count']; ?> membres
                                                </small>
                                            </div>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="join">
                                                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-user-plus me-1"></i>Rejoindre
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de création de groupe -->
    <div class="modal fade" id="createGroupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Créer un nouveau groupe</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <div class="mb-3">
                            <label for="group_name" class="form-label">Nom du groupe</label>
                            <input type="text" class="form-control" id="group_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="group_description" class="form-label">Description</label>
                            <textarea class="form-control" id="group_description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Créer le groupe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 