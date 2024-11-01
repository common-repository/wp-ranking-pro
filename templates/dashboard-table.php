<?php
$settings = $current_ranking->settings;

if (1 <= $current_ranking->id && $current_ranking->id <= 6) {
    include(dirname(WP_Ranking_PRO::file_path()) . '/templates/dashboard-table-default.php');
}
else {
    include(dirname(WP_Ranking_PRO::file_path()) . '/templates/dashboard-table-custom.php');
}
