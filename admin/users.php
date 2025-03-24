<?php
require_once '../includes/config.php';

// Vérification si l'utilisateur est admin
if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../login.php');
    exit;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete':
                if (isset($_POST['user_id'])) {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                }
                break;
            case 'toggle_admin':
                if (isset($_POST['user_id'])) {
                    $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
                    $stmt->execute([$_POST['user_id']]);
                }
                break;
        }
        header('Location: users.php');
        exit;
    }
}

include '../components/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users"></i> Utilisateurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="foods.php">
                            <i class="fas fa-utensils"></i> Aliments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="exercises.php">
                            <i class="fas fa-dumbbell"></i> Exercices
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="achievements.php">
                            <i class="fas fa-trophy"></i> Badges
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="stats.php">
                            <i class="fas fa-chart-bar"></i> Statistiques
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Gestion des utilisateurs</h1>
            </div>

            <!-- Search and filters -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchUser" placeholder="Rechercher un utilisateur...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary" onclick="exportUsers('csv')">
                            <i class="fas fa-download"></i> Exporter CSV
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="exportUsers('pdf')">
                            <i class="fas fa-file-pdf"></i> Exporter PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users table -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="usersTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nom d'utilisateur</th>
                                    <th>Email</th>
                                    <th>Date d'inscription</th>
                                    <th>Dernière connexion</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
                                while ($user = $stmt->fetch()) {
                                    echo "<tr>";
                                    echo "<td>{$user['id']}</td>";
                                    echo "<td>{$user['username']}</td>";
                                    echo "<td>{$user['email']}</td>";
                                    echo "<td>" . date('d/m/Y H:i', strtotime($user['created_at'])) . "</td>";
                                    echo "<td>" . ($user['updated_at'] ? date('d/m/Y H:i', strtotime($user['updated_at'])) : '-') . "</td>";
                                    echo "<td>";
                                    if ($user['is_admin']) {
                                        echo '<span class="badge bg-primary">Admin</span>';
                                    } else {
                                        echo '<span class="badge bg-secondary">Utilisateur</span>';
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<div class='btn-group'>";
                                    echo "<button type='button' class='btn btn-sm btn-info' onclick='viewUser({$user['id']})'><i class='fas fa-eye'></i></button>";
                                    echo "<form method='POST' style='display: inline;'>";
                                    echo "<input type='hidden' name='action' value='toggle_admin'>";
                                    echo "<input type='hidden' name='user_id' value='{$user['id']}'>";
                                    echo "<button type='submit' class='btn btn-sm btn-warning'><i class='fas fa-user-shield'></i></button>";
                                    echo "</form>";
                                    echo "<form method='POST' style='display: inline;' onsubmit='return confirm(\"Êtes-vous sûr de vouloir supprimer cet utilisateur ?\")'>";
                                    echo "<input type='hidden' name='action' value='delete'>";
                                    echo "<input type='hidden' name='user_id' value='{$user['id']}'>";
                                    echo "<button type='submit' class='btn btn-sm btn-danger'><i class='fas fa-trash'></i></button>";
                                    echo "</form>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de l'utilisateur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="userDetails">
                    <!-- Les détails seront chargés ici via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewUser(userId) {
    // À implémenter : charger les détails de l'utilisateur via AJAX
    $('#userDetailsModal').modal('show');
}

function exportUsers(format) {
    // À implémenter : export des utilisateurs
    alert('Export en ' + format + ' à implémenter');
}

// Recherche en temps réel
document.getElementById('searchUser').addEventListener('keyup', function() {
    var input = this.value.toLowerCase();
    var table = document.getElementById('usersTable');
    var rows = table.getElementsByTagName('tr');

    for (var i = 1; i < rows.length; i++) {
        var username = rows[i].getElementsByTagName('td')[1].textContent.toLowerCase();
        var email = rows[i].getElementsByTagName('td')[2].textContent.toLowerCase();
        
        if (username.includes(input) || email.includes(input)) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
});
</script>

<style>
.btn-group .btn {
    margin-right: 5px;
}

.badge {
    font-size: 0.8rem;
    padding: 0.5em 0.8em;
}
</style>

<?php include '../components/admin_footer.php'; ?> 