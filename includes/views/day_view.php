<?php

/*This whole code is connected to include/fetch_calendar_view.php */


$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
?>
<div class="calendar-header mb-4">
    <a href="#" class="calendar-nav-arrow" data-view="day" data-date="<?= date('Y-m-d', strtotime('-1 day', strtotime($selected_date))) ?>"><i class="fas fa-chevron-left"></i></a>
    <h3><?= date('l, F j, Y', strtotime($selected_date)) ?></h3>
    <a href="#" class="calendar-nav-arrow" data-view="day" data-date="<?= date('Y-m-d', strtotime('+1 day', strtotime($selected_date))) ?>"><i class="fas fa-chevron-right"></i></a>
</div>
<p>Content for Day View on **<?= $selected_date ?>** goes here.</p>