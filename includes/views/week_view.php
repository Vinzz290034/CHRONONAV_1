<?php

/*This whole code is connected to include/fetch_calendar_view.php */


$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_timestamp = strtotime($selected_date);
$monday_of_week = date('Y-m-d', strtotime('monday this week', $selected_timestamp));
$sunday_of_week = date('Y-m-d', strtotime('sunday this week', $selected_timestamp));
$prev_week = date('Y-m-d', strtotime('-1 week', $selected_timestamp));
$next_week = date('Y-m-d', strtotime('+1 week', $selected_timestamp));
?>
<div class="calendar-header mb-4">
    <a href="#" class="calendar-nav-arrow" data-view="week" data-date="<?= $prev_week ?>"><i class="fas fa-chevron-left"></i></a>
    <h3><?= date('F j, Y', strtotime($monday_of_week)) ?> - <?= date('F j, Y', strtotime($sunday_of_week)) ?></h3>
    <a href="#" class="calendar-nav-arrow" data-view="week" data-date="<?= $next_week ?>"><i class="fas fa-chevron-right"></i></a>
</div>
<div class="calendar-grid week-grid">
    <?php
    $current_day_ts = strtotime($monday_of_week);
    for ($i = 0; $i < 7; $i++) {
        $day_date = date('Y-m-d', $current_day_ts);
        $day_name = date('l', $current_day_ts);
        $is_selected = ($day_date === date('Y-m-d')) ? 'selected' : '';
        echo "<a href='#' class='calendar-day {$is_selected}' data-bs-toggle='modal' data-bs-target='#dayEventModal' data-date='{$day_date}'>";
        echo "<span>{$day_name}</span><strong>" . date('j', $current_day_ts) . "</strong></a>";
        $current_day_ts = strtotime('+1 day', $current_day_ts);
    }
    ?>
</div>