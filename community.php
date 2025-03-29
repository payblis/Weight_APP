<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Gérer les actions (like, comment, follow)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'like':
                if (isset($_POST['post_id'])) {
                    togglePostLike($_POST['post_id'], $user_id);
                }
                break;
            case 'comment':
                if (isset($_POST['post_id']) && isset($_POST['content'])) {
                    addComment($_POST['post_id'], $user_id, $_POST['content']);
                }
                break;
            case 'follow':
                if (isset($_POST['user_id'])) {
                    toggleFollow($_POST['user_id'], $user_id);
                }
                break;
        }
        redirect('community.php');
    }
}

// Récupérer les posts de la communauté
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
        ORDER BY cp.created_at DESC
        LIMIT 20";
$posts = fetchAll($sql);

// Récupérer les groupes de l'utilisateur
$user_groups = getUserGroups($user_id);

// Récupérer les utilisateurs suggérés
$sql = "SELECT u.id, u.username, u.avatar,
        (SELECT COUNT(*) FROM community_posts WHERE user_id = u.id) as posts_count,
        (SELECT COUNT(*) FROM user_follows WHERE following_id = u.id) as followers_count
        FROM users u
        WHERE u.id != ? AND u.id NOT IN (
            SELECT following_id FROM user_follows WHERE follower_id = ?
        )
        ORDER BY posts_count DESC, followers_count DESC
        LIMIT 5";
$suggested_users = fetchAll($sql, [$user_id, $user_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communauté - Weight Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'navigation.php'; ?>

    <div class="container py-4">
        <div class="row">
            <!-- Fil d'actualité principal -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Fil d'actualité</h5>
                        <a href="groups.php" class="btn btn-light btn-sm">
                            <i class="fas fa-users me-1"></i>Gérer les groupes
                        </a>
                    </div>
                    <div class="card-body">
                        <!-- Formulaire de création de post -->
                        <form action="create-post.php" method="POST" class="mb-4">
                            <div class="mb-3">
                                <textarea class="form-control" name="content" rows="3" placeholder="Partagez votre message avec la communauté..."></textarea>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="visibility" id="visibility_public" value="public" checked>
                                    <label class="form-check-label" for="visibility_public">
                                        <i class="fas fa-globe me-1"></i>Public
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="visibility" id="visibility_group" value="group">
                                    <label class="form-check-label" for="visibility_group">
                                        <i class="fas fa-users me-1"></i>Groupe
                                    </label>
                                </div>
                                <div id="group_select" class="ms-3" style="display: none;">
                                    <select class="form-select form-select-sm" name="group_id">
                                        <option value="">Sélectionnez un groupe</option>
                                        <?php foreach ($user_groups as $group): ?>
                                            <option value="<?php echo $group['id']; ?>">
                                                <?php echo htmlspecialchars($group['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>Publier
                                </button>
                            </div>
                        </form>

                        <!-- Liste des posts -->
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
                                        <?php elseif ($post['post_type'] === 'program'): ?>
                                            <div class="program-post">
                                                <i class="fas fa-dumbbell me-2"></i>
                                                <strong>Nouveau programme</strong>
                                                <p class="mb-0"><?php echo htmlspecialchars($post['reference_name']); ?></p>
                                            </div>
                                        <?php elseif ($post['post_type'] === 'goal'): ?>
                                            <div class="goal-post">
                                                <i class="fas fa-bullseye me-2"></i>
                                                <strong>Nouvel objectif</strong>
                                                <p class="mb-0">Objectif de poids : <?php echo number_format($post['reference_name'], 1); ?> kg</p>
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
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Utilisateurs suggérés -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Utilisateurs suggérés</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($suggested_users as $user): ?>
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo $user['avatar'] ?: 'assets/images/default-avatar.png'; ?>" 
                                     class="rounded-circle me-2" 
                                     width="40" 
                                     height="40" 
                                     alt="Avatar">
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['username']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo $user['posts_count']; ?> posts • 
                                        <?php echo $user['followers_count']; ?> abonnés
                                    </small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary follow-btn" 
                                        data-user-id="<?php echo $user['id']; ?>">
                                    <i class="fas fa-user-plus"></i>
                                </button>
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

            // Gestion des abonnements
            document.querySelectorAll('.follow-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = this.dataset.userId;
                    fetch('community.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=follow&user_id=${userId}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.classList.toggle('btn-primary');
                            this.classList.toggle('btn-outline-primary');
                            this.querySelector('i').classList.toggle('fa-user-plus');
                            this.querySelector('i').classList.toggle('fa-user-check');
                        }
                    });
                });
            });

            // Gestion de la visibilité du post
            const visibilityInputs = document.querySelectorAll('input[name="visibility"]');
            const groupSelect = document.getElementById('group_select');
            
            visibilityInputs.forEach(input => {
                input.addEventListener('change', function() {
                    groupSelect.style.display = this.value === 'group' ? 'block' : 'none';
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