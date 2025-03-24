<?php
require_once 'includes/config.php';
include 'components/header.php';
?>

<div class="hero-section">
    <div class="hero-content">
        <h1>Atteignez vos objectifs de poids avec l'IA</h1>
        <p class="hero-subtitle">Suivez votre alimentation, vos exercices et obtenez des conseils personnalisés alimentés par l'intelligence artificielle.</p>
        
        <?php if (!isLoggedIn()): ?>
        <div class="hero-cta">
            <a href="register.php" class="btn btn-primary btn-large">Commencer Gratuitement</a>
            <a href="login.php" class="btn btn-outline">Se Connecter</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<section class="features-section">
    <div class="container">
        <h2 class="section-title">Pourquoi choisir notre application ?</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <i class="fas fa-robot feature-icon"></i>
                <h3>Coach IA Personnel</h3>
                <p>Obtenez des recommandations personnalisées basées sur vos objectifs et votre progression.</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-chart-line feature-icon"></i>
                <h3>Suivi Précis</h3>
                <p>Suivez votre poids, vos calories et vos exercices avec des graphiques détaillés.</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-dumbbell feature-icon"></i>
                <h3>Plans d'Exercices</h3>
                <p>Des programmes d'entraînement adaptés à votre niveau et vos objectifs.</p>
            </div>
            
            <div class="feature-card">
                <i class="fas fa-utensils feature-icon"></i>
                <h3>Suggestions de Repas</h3>
                <p>Des recommandations de repas équilibrés générées par l'IA.</p>
            </div>
        </div>
    </div>
</section>

<section class="how-it-works">
    <div class="container">
        <h2 class="section-title">Comment ça marche ?</h2>
        
        <div class="steps-grid">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Définissez vos objectifs</h3>
                <p>Indiquez votre poids actuel, votre objectif et le temps souhaité.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Suivez vos progrès</h3>
                <p>Enregistrez quotidiennement votre poids et vos activités.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Recevez des conseils IA</h3>
                <p>Obtenez des recommandations personnalisées pour atteindre vos objectifs.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Prêt à commencer votre transformation ?</h2>
            <p>Rejoignez des milliers d'utilisateurs qui ont déjà atteint leurs objectifs de poids.</p>
            <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-primary btn-large">Commencer Maintenant</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
/* Styles spécifiques à la page d'accueil */
.hero-section {
    background: linear-gradient(135deg, var(--primary-color), #003399);
    color: white;
    padding: 4rem 1rem;
    text-align: center;
    margin-top: -2rem;
}

.hero-content {
    max-width: 800px;
    margin: 0 auto;
}

.hero-content h1 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.hero-subtitle {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.hero-cta {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.1rem;
}

.btn-outline {
    border: 2px solid white;
    color: white;
    background: transparent;
}

.btn-outline:hover {
    background: rgba(255,255,255,0.1);
}

.features-section,
.how-it-works {
    padding: 4rem 1rem;
}

.section-title {
    text-align: center;
    margin-bottom: 3rem;
    font-size: 2rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.feature-card {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.feature-icon {
    font-size: 2.5rem;
    color: var(--primary-color);
    margin-bottom: 1rem;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.step-card {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
}

.step-number {
    width: 40px;
    height: 40px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    font-weight: bold;
    margin: 0 auto 1rem;
}

.cta-section {
    background: linear-gradient(135deg, var(--primary-color), #003399);
    color: white;
    padding: 4rem 1rem;
    text-align: center;
    margin-top: 2rem;
}

.cta-content {
    max-width: 600px;
    margin: 0 auto;
}

.cta-content h2 {
    margin-bottom: 1rem;
}

.cta-content p {
    margin-bottom: 2rem;
    opacity: 0.9;
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .hero-cta {
        flex-direction: column;
    }
    
    .features-grid,
    .steps-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'components/footer.php'; ?> 