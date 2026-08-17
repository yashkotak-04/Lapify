<?php
// detail.php - Alias redirect to laptop-details.php
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    header("Location: laptop-details.php?id=" . $id);
} else {
    header("Location: buy.php");
}
exit();
