<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>

    <link rel="stylesheet" href="./styles/index.css" class="css">
    <link rel="stylesheet" href="./styles/style.css" class="css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <?php 
        include_once("header.inc");
        include_once("cart.inc");
    ?>
    <div class="container">
        <section class="main-slide">
            <div class="section-wrap"></div>
            <div class="hero-content">
                <h1>Long Chau Pharmacy</h1>
                <h3>Caring for a better life with medication.</h3>
                <div class="button-row">
                    <button class="discover">
                        <span>Discover more</span>
                    </button>
                </div>
            </div>
        </section>
       </div>
    <section class="feature-row">
        <div class="row-content">
            <div class="feature">
                <span class="ft-text">
                    Pain Relief
                </span>
                <div class="ft-content">
                    <h2>Pain Relief</h2>
                    <p> Fast-acting formulas that soothe pain and inflammation to help you stay active.</p>
                </div>
            </div>
            <div class="feature">
                <span class="ft-text">
                    Infection Control
                </span>
                <div class="ft-content">
                    <h2>Infection Control</h2>
                    <p>Potent agents that target and eliminate pathogens to keep you healthy.</p>
                </div>
            </div>
            <div class="feature">
                <span class="ft-text">
                    Beauty Care
                </span>
                <div class="ft-content">
                    <h2>Beauty Care</h2>
                    <p>Nourishing products that restore and enhance your skin’s natural radiance.</p>
                </div>
            </div>
            <div class="feature">
                <span class="ft-text">
                    Supplements
                </span>
                <div class="ft-content">
                    <h2>Supplements</h2>
                    <p>Premium vitamins and minerals that fill nutritional gaps and boost overall wellness.</p>
                </div>
            </div>
        </div>
    </section>
    <?php 
        include_once("footer.inc");
    ?>
</body>
<script src="./cart.js"></script>
</html>