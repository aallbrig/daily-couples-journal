<?php

$title = "Daily Couples Journal";
include("views/html_top.php");

$tomorrow = date("Y-m-d", strtotime("+1 day"));
$start_date = $_GET['start_date'] ? $_GET['start_date'] : $tomorrow;
?>
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="card shadow my-5">
                    <div class="card-header text-center">
                        <h2 class="card-title">Payment Complete!</h2>
                        <p class="card-subtitle mb-2 text-muted">Congrats on signing up for this experience!</p>
                    </div>
                    <div class="card-body">
                        <h5>Next Steps...</h5>
                        <p>You will receive texts as a couple starting on <b id="start_date"><?php echo $start_date; ?></b>.</p>
                        <p>Once you receive the text for the day, it will be up to the two of you to have the conversation!</p>
                        <p>Remember to have fun! 📱</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
include("views/html_bottom.php");
