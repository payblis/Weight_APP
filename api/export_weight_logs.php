<?php
require_once '../includes/config.php';
require_once '../vendor/autoload.php'; // Pour TCPDF

// Vérification si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// Récupération du format d'export
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
$userId = $_SESSION['user_id'];

try {
    // Récupération des données
    $stmt = $pdo->prepare("
        SELECT dl.*, 
               COALESCE(LAG(dl.weight) OVER (ORDER BY dl.date), dl.weight) as previous_weight
        FROM daily_logs dl
        WHERE dl.user_id = ?
        ORDER BY dl.date DESC
    ");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll();

    // Récupération des informations de l'utilisateur
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if ($format === 'csv') {
        // Export CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=historique_poids_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // En-tête UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes des colonnes
        fputcsv($output, ['Date', 'Poids (kg)', 'Variation (kg)', 'Notes']);
        
        // Données
        foreach ($logs as $log) {
            $variation = $log['weight'] - $log['previous_weight'];
            fputcsv($output, [
                date('d/m/Y', strtotime($log['date'])),
                number_format($log['weight'], 1),
                $variation != 0 ? number_format($variation, 1) : '0',
                $log['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        exit;

    } elseif ($format === 'pdf') {
        // Export PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Informations du document
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor($user['username']);
        $pdf->SetTitle('Historique de poids');
        
        // En-tête et pied de page
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(true);
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        
        // Marges
        $pdf->SetMargins(PDF_MARGIN_LEFT, 20, PDF_MARGIN_RIGHT);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        
        // Saut de page automatique
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        // Police par défaut
        $pdf->SetFont('dejavusans', '', 10);
        
        // Ajout d'une page
        $pdf->AddPage();
        
        // Titre
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 10, 'Historique de poids', 0, 1, 'C');
        $pdf->Ln(10);
        
        // En-tête du tableau
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(40, 7, 'Date', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Poids (kg)', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'Variation (kg)', 1, 0, 'C', true);
        $pdf->Cell(70, 7, 'Notes', 1, 1, 'C', true);
        
        // Données du tableau
        $pdf->SetFont('dejavusans', '', 10);
        foreach ($logs as $log) {
            $variation = $log['weight'] - $log['previous_weight'];
            
            $pdf->Cell(40, 6, date('d/m/Y', strtotime($log['date'])), 1, 0, 'C');
            $pdf->Cell(40, 6, number_format($log['weight'], 1), 1, 0, 'C');
            
            // Coloration de la variation selon le signe
            if ($variation > 0) {
                $pdf->SetTextColor(255, 0, 0); // Rouge pour gain
            } elseif ($variation < 0) {
                $pdf->SetTextColor(0, 128, 0); // Vert pour perte
            }
            $pdf->Cell(40, 6, ($variation > 0 ? '+' : '') . number_format($variation, 1), 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0); // Retour à noir
            
            $pdf->Cell(70, 6, $log['notes'] ?? '', 1, 1, 'L');
        }
        
        // Statistiques
        $pdf->Ln(10);
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'Statistiques', 0, 1, 'L');
        
        $pdf->SetFont('dejavusans', '', 10);
        $stats = [
            'Premier enregistrement' => date('d/m/Y', strtotime(end($logs)['date'])),
            'Dernier enregistrement' => date('d/m/Y', strtotime($logs[0]['date'])),
            'Poids initial' => number_format(end($logs)['weight'], 1) . ' kg',
            'Poids actuel' => number_format($logs[0]['weight'], 1) . ' kg',
            'Variation totale' => number_format($logs[0]['weight'] - end($logs)['weight'], 1) . ' kg'
        ];
        
        foreach ($stats as $label => $value) {
            $pdf->Cell(60, 6, $label . ':', 0, 0, 'L');
            $pdf->Cell(0, 6, $value, 0, 1, 'L');
        }
        
        // Envoi du PDF
        $pdf->Output('historique_poids_' . date('Y-m-d') . '.pdf', 'D');
        exit;

    } else {
        throw new Exception('Format non supporté');
    }

} catch (Exception $e) {
    error_log("Erreur lors de l'export : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Erreur lors de l'export"]);
}
?> 