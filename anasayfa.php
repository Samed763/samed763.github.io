<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

// Ödeme zamanı bir haftadan az olan ödemeler için otomatik alarm oluştur
$current_date = date('Y-m-d');
$one_week_later = date('Y-m-d', strtotime('+1 week'));

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    if (isset($_POST['employee_salary_id'])) {
        $employee_salary_id = $_POST['employee_salary_id'];

        // Get the current payment date from the database
        $sql_get_payment_date = "SELECT payment_date FROM employee_salaries WHERE id = ?";
        if ($stmt_get_payment_date = $conn->prepare($sql_get_payment_date)) {
            $stmt_get_payment_date->bind_param("i", $employee_salary_id);
            $stmt_get_payment_date->execute();
            $stmt_get_payment_date->bind_result($current_payment_date);
            $stmt_get_payment_date->fetch();
            $stmt_get_payment_date->close();

            // Calculate the new payment date
            $new_payment_date = date('Y-m-d', strtotime('+1 month', strtotime($current_payment_date)));

            // Çalışan maaş durumunu güncelle
            $sql_update_salary = "UPDATE employee_salaries SET status = 'Paid', payment_date = ? WHERE id = ?";
            if ($stmt_update_salary = $conn->prepare($sql_update_salary)) {
                $stmt_update_salary->bind_param("si", $new_payment_date, $employee_salary_id);

                if ($stmt_update_salary->execute()) {
                    echo "<p>Çalışan maaşı başarıyla güncellendi!</p>";
                } else {
                    echo "<p>Hata: " . $stmt_update_salary->error . "</p>";
                }

                $stmt_update_salary->close();
            } else {
                echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
        }
    }

    if (isset($_POST['alarm_id']) && isset($_POST['confirm_inactivate'])) {
        $alarm_id = $_POST['alarm_id'];

        // Update the alarm status to 'Inactive'
        $sql_update_alarm = "UPDATE alarm SET status = 'Inactive' WHERE id = ?";
        if ($stmt_update_alarm = $conn->prepare($sql_update_alarm)) {
            $stmt_update_alarm->bind_param("i", $alarm_id);

            if ($stmt_update_alarm->execute()) {
                echo "<p>Alarm başarıyla inaktif yapıldı!</p>";
            } else {
                echo "<p>Hata: " . $stmt_update_alarm->error . "</p>";
            }

            $stmt_update_alarm->close();
        } else {
            echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
        }
    }
}

// Ensure the 'status' column exists in the 'employee_salaries' table
$sql_payments = "SELECT id, payment_date, status FROM employee_salaries WHERE payment_date BETWEEN ? AND ? AND status = 'Unpaid'";
if ($stmt_payments = $conn->prepare($sql_payments)) {
    $stmt_payments->bind_param("ss", $current_date, $one_week_later);
    $stmt_payments->execute();
    $result_payments = $stmt_payments->get_result();

    $upcoming_payments = [];
    while ($row = $result_payments->fetch_assoc()) {
        $upcoming_payments[] = $row;
    }

    $stmt_payments->close();
} else {
    echo "Error preparing statement for payments: " . $conn->error;
}



// Çalışanları sorgula
$query_employees = "SELECT id, employee_name, salary_amount, payment_date, status FROM employee_salaries";
$result_employees = $conn->query($query_employees);

$employees = [];
if ($result_employees) {
    while ($row = $result_employees->fetch_assoc()) {
        $employees[] = $row;
    }
} else {
    echo "Error querying employees: " . $conn->error;
}

// Son bir hafta içinde oluşturulan ve aktif olan alarmları sorgula
$query_alarms = "SELECT id, alarm_title, alarm_date, alarm_message, status FROM alarm WHERE alarm_date BETWEEN ? AND ? AND status = 'Active'";
if ($stmt_alarms = $conn->prepare($query_alarms)) {
    $stmt_alarms->bind_param("ss", $current_date, $one_week_later);
    $stmt_alarms->execute();
    $result_alarms = $stmt_alarms->get_result();

    $upcoming_alarms = [];
    if ($result_alarms) {
        while ($row = $result_alarms->fetch_assoc()) {
            $upcoming_alarms[] = $row;
        }
    } else {
        echo "Error fetching alarms: " . $stmt_alarms->error;
    }

    $stmt_alarms->close();
} else {
    echo "Error preparing statement for alarms: " . $conn->error;
}

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <div class="navbar">
        <a href="anasayfa.php">Ana Sayfa</a>
        <a href="evraklar.php">Evraklar</a>
        <a href="finansal.php">Finansal</a>
        <a href="alarm.php">Alarm</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
    <div class="pagedata">
        <a href="create_employee_salary.php" class="create-document-button">Create Employee Salary</a>
        <a href="create_financial_transaction.php" class="create-document-button">Create Financial Transaction</a>
        <a href="create_income_expense.php" class="create-document-button">Create Income/Expense</a>
    </div>
    <div class="upcoming-payments">
        <h2>Yaklaşan Ödemeler</h2>
        <div class="payment-list">
            <?php if (!empty($upcoming_payments)) : ?>
                <?php foreach ($upcoming_payments as $payment) : ?>
                    <div class="payment-card">
                        <p><strong>Ödeme Tarihi:</strong> <?php echo $payment['payment_date']; ?></p>
                        <p>Yaklaşan bir ödeme var!</p>
                        <?php if ($payment['status'] !== 'Paid') : ?>
                            <form method="POST" action="">
                                <input type="hidden" name="employee_salary_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="payment_date" value="<?php echo $payment['payment_date']; ?>">
                                <button type="submit" class="submit-button">Ödeme Yap</button>
                            </form>
                        <?php else : ?>
                            <p>Ödeme Durumu: Ödendi</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Yaklaşan ödeme bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="upcoming-alarms">
        <h2>Yaklaşan Alarmlar</h2>
        <div class="alarm-list">
            <?php if (!empty($upcoming_alarms)) : ?>
                <?php foreach ($upcoming_alarms as $alarm) : ?>
                    <div class="alarm-card">
                        <p><strong>Alarm Tarihi:</strong> <?php echo $alarm['alarm_date']; ?></p>
                        <p><strong>Başlık:</strong> <?php echo $alarm['alarm_title']; ?></p>
                        <p>Yaklaşan bir alarm var!</p>
                        <?php if ($alarm['status'] === 'Active') : ?>
                            <form method="POST" action="">
                                <input type="hidden" name="alarm_id" value="<?php echo $alarm['id']; ?>">
                                <button type="submit" class="submit-button" name="confirm_inactivate">Alarmı İnaktif Yap</button>
                            </form>
                        <?php else : ?>
                            <form method="POST" action="">
                                <input type="hidden" name="alarm_id" value="<?php echo $alarm['id']; ?>">
                                <button type="submit" class="submit-button" name="confirm_inactivate">Alarmı İnaktif Yap</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p>Yaklaşan alarm bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
    <div class="employees">
        <h2>Çalışanlar</h2>
        <div class="employee-list">
            <?php foreach ($employees as $employee) : ?>
                <div class="employee-card">
                    <h3>Çalışan: <?php echo $employee['employee_name']; ?></h3>
                    <p><strong>Maaş:</strong> <?php echo $employee['salary_amount']; ?> TL</p>
                    <p><strong>Ödeme Tarihi:</strong> <?php echo $employee['payment_date']; ?></p>
                    <p><strong>Durum:</strong> <?php echo $employee['status']; ?></p>
                    <?php if ($employee['status'] === 'unpaid') : ?>
                        <form method="POST" action="">
                            <input type="hidden" name="employee_salary_id" value="<?php echo $employee['id']; ?>">
                            <button type="submit" class="submit-button">Ödeme Yap</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; 2024 Tüm Hakları Saklıdır.</p>
            <ul class="footer-links">
                <li><a href="hakkinda.html">Hakkında</a></li>
                <li><a href="iletisim.html">İletişim</a></li>
                <li><a href="gizlilik-politikasi.html">Gizlilik Politikası</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
