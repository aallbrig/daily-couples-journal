<?php

$title = "Daily Couples Journal";
include("views/html_top.php");

$tomorrow = date("Y-m-d", strtotime("+1 day"));
$start_date = $_GET['start_date'] ? $_GET['start_date'] : $tomorrow;
?>
    <div class="container">
        <h2 class="modal-title my-5">Payment Complete!</h2>

        <h5>Next Steps...</h5>
        <p>Congrats on signing up for this experience!</p>
        <p>You will receive texts as a couple starting on <b id="start_date"><?php echo $start_date; ?></b>.</p>
        <p>Once you receive the text for the day, it will be up to the two of you to have the conversation!</p>
        <p>Remember to have fun! 📱</p>
    </div>

<?php
include("views/html_bottom.php");
