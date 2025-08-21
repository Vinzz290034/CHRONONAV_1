<?php
/*This whole code is connected to include/fetch_calendar_view.php */


$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
?>
<div class="calendar-year-view">
    <div class="calendar-header mb-4">
        <a href="#" class="calendar-nav-arrow" data-view="year" data-year="<?= $selected_year - 1 ?>">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h2 class="mb-0"><?= $selected_year ?></h2>
        <a href="#" class="calendar-nav-arrow" data-view="year" data-year="<?= $selected_year + 1 ?>">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    <div class="year-grid">
        <?php for ($month = 1; $month <= 12; $month++) { ?>
            <div class="month-card">
                <h5 class="month-title"><?= date('F', mktime(0, 0, 0, $month, 1, $selected_year)) ?></h5>
                <div class="calendar-days-header-small">
                    <div>S</div>
                    <div>M</div>
                    <div>T</div>
                    <div>W</div>
                    <div>T</div>
                    <div>F</div>
                    <div>S</div>
                </div>
                <div class="month-days-grid">
                    <?php
                    $first_day_of_month = mktime(0, 0, 0, $month, 1, $selected_year);
                    $num_days_in_month = date('t', $first_day_of_month);
                    $first_day_of_week = date('N', $first_day_of_month);
                    
                    if ($first_day_of_week == 7) {
                        $start_offset = 0;
                    } else {
                        $start_offset = $first_day_of_week;
                    }

                    for ($i = 0; $i < $start_offset; $i++) {
                        echo '<div class="day-cell empty"></div>';
                    }

                    for ($day = 1; $day <= $num_days_in_month; $day++) {
                        $full_date = date("Y-m-d", mktime(0, 0, 0, $month, $day, $selected_year));
                        $is_today = ($full_date === date('Y-m-d')) ? 'today' : '';
                        echo "<div class='day-cell {$is_today}'>{$day}</div>";
                    }
                    ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<style>
/* Add this to your existing schedules.css or embed it within the file */

/* General styling for the header */
.calendar-year-view .calendar-header {
    display: flex;
    justify-content: center;
    align-items: center;
    color: #2c3e50; /* A dark, visible color for the text */
    gap: 20px;
}

/* Style for the year text */
.calendar-year-view .calendar-header h2 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

/* Style for the navigation arrows */
.calendar-year-view .calendar-nav-arrow {
    font-size: 1.5rem;
    color: #3498db; /* A distinct color for the arrows */
    text-decoration: none;
    transition: color 0.3s ease;
}

.calendar-year-view .calendar-nav-arrow:hover {
    color: #2980b9; /* A slightly darker color on hover */
}

/* Style for the month cards and grid as shown in the image */
.year-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 30px;
}

.month-card {
    background-color: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.month-title {
    font-weight: 600;
    font-size: 1.25rem;
    margin-bottom: 10px;
    color: #34495e; /* A dark color for month titles */
}

.calendar-days-header-small {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-weight: 600;
    color: #7f8c8d; /* A subtle color for day headers */
    margin-bottom: 5px;
}

.month-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 2px;
}

.day-cell {
    padding: 8px 0;
    text-align: center;
    font-size: 0.95rem;
    color: #333;
}

.day-cell.empty {
    visibility: hidden;
}

.day-cell.today {
    background-color: #3498db;
    color: #fff;
    border-radius: 4px;
    font-weight: bold;
}
</style>