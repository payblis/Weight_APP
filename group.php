<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Vérifier si l'ID du groupe est fourni
if (!isset($_GET['id'])) {
    redirect('groups.php');
}

$group_id = $_GET['id'];

// Vérifier si l'utilisateur est membre du groupe
if (!isGroupMember($group_id, $user_id)) {
    $_SESSION['error_message'] = "Vous n'êtes pas membre de ce groupe.";
    redirect('groups.php');
}

// Récupérer les informations du groupe
$sql = "SELECT g.*, u.username as creator_name, 
        (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
        FROM community_groups g
        JOIN users u ON g.created_by = u.id
        WHERE g.id = ?";
$group = fetchOne($sql, [$group_id]);

if (!$group) {
    $_SESSION['error_message'] = "Ce groupe n'existe pas.";
    redirect('groups.php');
}

// Récupérer les membres du groupe
$members = getGroupMembers($group_id);

// Récupérer les posts du groupe
$sql = "SELECT cp.*, u.username, u.avatar,
        CASE 
            WHEN cp.post_type = 'meal' THEN m.total_calories
            WHEN cp.post_type = 'exercise' THEN el.calories_burned
            ELSE NULL
        END as calories,
        CASE 
            WHEN cp.post_type = 'meal' THEN m.notes
            WHEN cp.post_type = 'exercise' THEN el.notes
            ELSE NULL
        END as notes,
        CASE 
            WHEN cp.post_type = 'program' THEN p.name
            WHEN cp.post_type = 'goal' THEN g.target_weight
            ELSE NULL
        END as reference_name
        FROM community_posts cp
        JOIN users u ON cp.user_id = u.id
        LEFT JOIN meals m ON cp.reference_id = m.id AND cp.post_type = 'meal'
        LEFT JOIN exercise_logs el ON cp.reference_id = el.id AND cp.post_type = 'exercise'
        LEFT JOIN programs p ON cp.reference_id = p.id AND cp.post_type = 'program'
        LEFT JOIN goals g ON cp.reference_id = g.id AND cp.post_type = 'goal'
        WHERE cp.group_id = ?
        ORDER BY cp.created_at DESC";
$posts = fetchAll($sql, [$group_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($group['name']); ?> - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <!-- En-tête du groupe -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="h3 mb-1"><?php echo htmlspecialchars($group['name']); ?></h1>
                        <p class="text-muted mb-2"><?php echo htmlspecialchars($group['description']); ?></p>
                        <small class="text-muted">
                            Créé par <?php echo htmlspecialchars($group['creator_name']); ?> • 
                            <?php echo $group['member_count']; ?> membres
                        </small>
                    </div>
                    <div>
                        <?php if (isGroupAdmin($group_id, $user_id)): ?>
                            <span class="badge bg-primary me-2">Admin</span>
                        <?php endif; ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="leave">
                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="fas fa-sign-out-alt me-1"></i>Quitter le groupe
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Fil d'actualité -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Fil d'actualité</h5>
                    </div>
                    <div class="card-body">
                        <!-- Formulaire de création de post -->
                        <form action="create-post.php" method="POST" class="mb-4">
                            <input type="hidden" name="visibility" value="group">
                            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                            <div class="mb-3">
                                <textarea class="form-control" name="content" rows="3" placeholder="Partagez votre message avec le groupe..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i>Publier
                            </button>
                        </form>

                        <!-- Liste des posts -->
                        <?php if (empty($posts)): ?>
                            <p class="text-muted mb-0">Aucun post dans ce groupe.</p>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <!-- En-tête du post -->
                                        <div class="d-flex align-items-center mb-3">
                                            <img src="<?php echo $post['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                                 class="rounded-circle me-2" 
                                                 width="40" 
                                                 height="40" 
                                                 alt="Avatar">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($post['username']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($post['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Contenu du post -->
                                        <div class="post-content">
                                            <?php if ($post['post_type'] === 'meal'): ?>
                                                <div class="meal-post">
                                                    <i class="fas fa-utensils me-2"></i>
                                                    <strong>Repas partagé</strong>
                                                    <p class="mb-0"><?php echo number_format($post['calories']); ?> calories</p>
                                                    <?php if ($post['notes']): ?>
                                                        <p class="mb-0"><?php echo htmlspecialchars($post['notes']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php elseif ($post['post_type'] === 'exercise'): ?>
                                                <div class="exercise-post">
                                                    <i class="fas fa-running me-2"></i>
                                                    <strong>Exercice partagé</strong>
                                                    <p class="mb-0"><?php echo number_format($post['calories']); ?> calories brûlées</p>
                                                    <?php if ($post['notes']): ?>
                                                        <p class="mb-0"><?php echo htmlspecialchars($post['notes']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions du post -->
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary me-2 like-btn" 
                                                        data-post-id="<?php echo $post['id']; ?>">
                                                    <i class="fas fa-heart"></i>
                                                    <span class="likes-count"><?php echo $post['likes_count']; ?></span>
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary comment-btn" 
                                                        data-post-id="<?php echo $post['id']; ?>">
                                                    <i class="fas fa-comment"></i>
                                                    <span class="comments-count"><?php echo $post['comments_count']; ?></span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Section des commentaires -->
                                        <div class="comments-section mt-3" style="display: none;">
                                            <form class="comment-form mb-2" data-post-id="<?php echo $post['id']; ?>">
                                                <div class="input-group">
                                                    <input type="text" class="form-control form-control-sm" 
                                                           placeholder="Écrire un commentaire...">
                                                    <button class="btn btn-sm btn-primary" type="submit">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                </div>
                                            </form>
                                            <div class="comments-list">
                                                <!-- Les commentaires seront chargés dynamiquement -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Membres du groupe -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Membres du groupe</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($members as $member): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $member['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                     class="rounded-circle me-2" 
                                     width="40" 
                                     height="40" 
                                     alt="Avatar">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($member['username']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $member['role'] === 'admin' ? 'Administrateur' : 'Membre'; ?>
                                    </small>
                                </div>
                                <?php if (isGroupAdmin($group_id, $user_id) && $member['id'] !== $user_id): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="remove_member">
                                        <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-user-minus"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des likes
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    fetch('community.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=like&post_id=${postId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const countSpan = this.querySelector('.likes-count');
                            countSpan.textContent = data.likes_count;
                            this.classList.toggle('btn-primary');
                            this.classList.toggle('btn-outline-primary');
                        }
                    });
                });
            });

            // Gestion des commentaires
            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const postId = this.dataset.postId;
                    const commentsSection = this.closest('.card-body')
                        .querySelector('.comments-section');
                    commentsSection.style.display = 
                        commentsSection.style.display === 'none' ? 'block' : 'none';
                    
                    if (commentsSection.style.display === 'block') {
                        loadComments(postId);
                    }
                });
            });

            // Soumission des commentaires
            document.querySelectorAll('.comment-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const postId = this.dataset.postId;
                    const input = this.querySelector('input');
                    const content = input.value.trim();
                    
                    if (content) {
                        fetch('community.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=comment&post_id=${postId}&content=${encodeURIComponent(content)}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                input.value = '';
                                loadComments(postId);
                                updateCommentsCount(postId);
                            }
                        });
                    }
                });
            });

            // Fonction pour charger les commentaires
            function loadComments(postId) {
                fetch(`get-comments.php?post_id=${postId}`)
                    .then(response => response.json())
                    .then(data => {
                        const commentsList = document.querySelector(`[data-post-id="${postId}"]`)
                            .closest('.comments-section')
                            .querySelector('.comments-list');
                        commentsList.innerHTML = data.comments.map(comment => `
                            <div class="comment mb-2">
                                <div class="d-flex align-items-center">
                                    <img src="${comment.avatar || 'assets/images/default-avatar.png'}" 
                                         class="rounded-circle me-2" 
                                         width="24" 
                                         height="24" 
                                         alt="Avatar">
                                    <div>
                                        <strong>${comment.username}</strong>
                                        <p class="mb-0 small">${comment.content}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    });
            }

            // Fonction pour mettre à jour le compteur de commentaires
            function updateCommentsCount(postId) {
                fetch(`get-comments-count.php?post_id=${postId}`)
                    .then(response => response.json())
                    .then(data => {
                        const countSpan = document.querySelector(`[data-post-id="${postId}"]`)
                            .closest('.card-body')
                            .querySelector('.comments-count');
                        countSpan.textContent = data.count;
                    });
            }
        });
    </script>
</body>
</html> 