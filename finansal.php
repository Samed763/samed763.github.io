<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

// Genel Finansal Durum
$query_income = "SELECT SUM(payed_amount) as total_income FROM financial_transactions WHERE transaction_type = 'income'";

$query_expense = "SELECT SUM(payed_amount) as total_expense FROM financial_transactions WHERE transaction_type = 'expense'";
$result_income = $conn->query($query_income);
$result_expense = $conn->query($query_expense);

$total_income = $result_income ? $result_income->fetch_assoc()['total_income'] : 0;
$total_expense = $result_expense ? $result_expense->fetch_assoc()['total_expense'] : 0;

// Include income and expense from the new table
$query_new_income = "SELECT SUM(payed_amount) as total_income FROM income_expense WHERE category = 'income' AND status = 'completed'";
$query_new_expense = "SELECT SUM(payed_amount) as total_expense FROM income_expense WHERE category = 'expense' AND status = 'completed'";
$result_new_income = $conn->query($query_new_income);
$result_new_expense = $conn->query($query_new_expense);

$total_income += $result_new_income ? $result_new_income->fetch_assoc()['total_income'] : 0;
$total_expense += $result_new_expense ? $result_new_expense->fetch_assoc()['total_expense'] : 0;

// Müvekkil Ödeme Takibi
$query_overdue = "SELECT client_name, payment_amount, payment_date FROM financial_transactions ft JOIN documents d ON ft.document_id = d.id WHERE ft.status = 'overdue' AND ft.payment_amount != 0";
$result_overdue = $conn->query($query_overdue);

$overdue_payments = [];
if ($result_overdue) {
    while ($row = $result_overdue->fetch_assoc()) {
        $overdue_payments[] = $row;
    }
}

// Dava Bazlı Gelir ve Gider
$query_case_income = "SELECT d.document_name, SUM(ft.payment_amount - ft.payed_amount) as expected_income 
                      FROM financial_transactions ft 
                      JOIN documents d ON ft.document_id = d.id 
                      WHERE ft.transaction_type = 'income' AND ft.payment_amount != 0 AND ft.status != 'completed' 
                      GROUP BY d.document_name";
$query_case_expense = "SELECT d.document_name, SUM(ft.payed_amount) as total_expense 
                       FROM financial_transactions ft 
                       JOIN documents d ON ft.document_id = d.id 
                       WHERE ft.transaction_type = 'expense' AND ft.payed_amount != 0 
                       GROUP BY d.document_name";
$result_case_income = $conn->query($query_case_income);
$result_case_expense = $conn->query($query_case_expense);

$case_income = [];
$case_expense = [];
if ($result_case_income) {
    while ($row = $result_case_income->fetch_assoc()) {
        $case_income[] = $row;
    }
}
if ($result_case_expense) {
    while ($row = $result_case_expense->fetch_assoc()) {
        $case_expense[] = $row;
    }
}

// Aylık/Yıllık Finansal Raporlar
$query_monthly_report = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, SUM(payed_amount) as total_amount, transaction_type FROM financial_transactions WHERE payed_amount != 0 GROUP BY month, transaction_type";
$result_monthly_report = $conn->query($query_monthly_report);

$monthly_reports = [];
if ($result_monthly_report) {
    while ($row = $result_monthly_report->fetch_assoc()) {
        $monthly_reports[$row['month']][$row['transaction_type']] = $row['total_amount'];
    }
}

// Yaklaşan ve Geciken Ödemeler
$query_upcoming = "SELECT client_name, payment_amount, payment_date FROM financial_transactions ft JOIN documents d ON ft.document_id = d.id WHERE ft.status = 'upcoming' AND ft.payment_amount != 0";
$result_upcoming = $conn->query($query_upcoming);

$upcoming_payments = [];
if ($result_upcoming) {
    while ($row = $result_upcoming->fetch_assoc()) {
        $upcoming_payments[] = $row;
    }
}

// Çalışan Maaş ve Prim Yönetimi
$query_salaries_unpaid = "SELECT id, employee_name, salary_amount, payment_date FROM employee_salaries WHERE status = 'unpaid' AND salary_amount != 0";
$result_salaries_unpaid = $conn->query($query_salaries_unpaid);

$unpaid_salaries = [];
$total_unpaid_salaries = 0;
if ($result_salaries_unpaid) {
    while ($row = $result_salaries_unpaid->fetch_assoc()) {
        $unpaid_salaries[] = $row;
        $total_unpaid_salaries += $row['salary_amount'];
    }
}

// Giderler (Paid Salaries)
$query_salaries_paid = "SELECT employee_name, salary_amount, payment_date FROM employee_salaries WHERE status = 'paid' AND salary_amount != 0";
$result_salaries_paid = $conn->query($query_salaries_paid);

$paid_salaries = [];
$total_paid_salaries = 0;
if ($result_salaries_paid) {
    while ($row = $result_salaries_paid->fetch_assoc()) {
        $paid_salaries[] = $row;
        $total_paid_salaries += $row['salary_amount'];
    }
}

$total_expense += $total_paid_salaries;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_salary'])) {
    $salary_id = $_POST['salary_id'];
    $payment_amount = $_POST['payment_amount'];

    // Get the current payment date from the database
    $sql_get_payment_date = "SELECT payment_date FROM employee_salaries WHERE id = ?";
    if ($stmt_get_payment_date = $conn->prepare($sql_get_payment_date)) {
        $stmt_get_payment_date->bind_param("i", $salary_id);
        $stmt_get_payment_date->execute();
        $stmt_get_payment_date->bind_result($current_payment_date);
        $stmt_get_payment_date->fetch();
        $stmt_get_payment_date->close();

        // Calculate the new payment date
        $new_payment_date = date('Y-m-d', strtotime('+1 month', strtotime($current_payment_date)));

        // Update the salary status to 'paid'
        $update_query = "UPDATE employee_salaries SET status = 'paid', payment_date = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $new_payment_date, $salary_id);
        $stmt->execute();
        $stmt->close();

        // Insert the payment as an expense in financial transactions
        $insert_query = "INSERT INTO financial_transactions (document_id, payment_amount, transaction_type, status, payed_amount) VALUES (?, ?, 'expense', 'completed', ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("idd", $salary_id, $payment_amount, $payment_amount);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
    }
}

// Ödeme tarihi geçmiş olanları 'overdue' olarak güncelle
$current_date = date('Y-m-d');
$update_overdue_query = "UPDATE financial_transactions SET status = 'overdue' WHERE payment_date < ? AND status != 'completed'";
$stmt = $conn->prepare($update_overdue_query);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$stmt->close();

$balance = $total_income - $total_expense - $total_unpaid_salaries;

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finansal Durum</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        
    </style>
</head>
<body>
<div class="navbar">
        <a href="anasayfa.php">Ana Sayfa</a>
        <a href="evraklar.php">Evraklar</a>
        <a href="finansal.php">Finansal</a>
        <a href="alarm.php">Alarm</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="content">
        <h1>Genel Finansal Durum</h1>
        <div class="financial-summary">
            <div>
                <p>Toplam Gelir: <span><?php echo $total_income; ?> TL</span></p>
            </div>
            <div>
                <p>Toplam Gider: <span><?php echo $total_expense; ?> TL</span></p>
            </div>
        </div>
        <p>Gelir: <span style="font-weight: bold; font-size: 24px; padding: 10px;"><?php echo $balance; ?> TL</span></p>


        <h2>Müvekkil Ödeme Takibi</h2>
        <h3>Geciken Ödemeler</h3>
        <ul>
            <?php foreach ($overdue_payments as $payment) : ?>
                <li>
                    <span><?php echo $payment['client_name']; ?></span>
                    <span class="amount"><?php echo $payment['payment_amount']; ?> TL</span>
                    <span class="date"><?php echo $payment['payment_date']; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Dava Bazlı Gelir ve Gider</h2>
        <div class="case-summary">
            <div>
                <h3>Beklenen Gelir</h3>
                <ul>
                    <?php foreach ($case_income as $income) : ?>
                        <?php if ($income['expected_income'] != 0) : ?>
                            <li>
                                <span><?php echo $income['document_name']; ?></span>
                                <span class="amount"><?php echo $income['expected_income']; ?> TL</span>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            
        </div>

        <h2>Aylık Finansal Raporlar</h2>
        <div class="report-summary">
            <?php
            $months = [
                '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
                '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
                '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
            ];
            $current_year = date('Y');
            $current_month = date('m');
            $last_three_months = [];
            for ($i = 0; $i < 3; $i++) {
                $month = date('m', strtotime("-$i month"));
                $year = date('Y', strtotime("-$i month"));
                $last_three_months[] = ['month' => $month, 'year' => $year];
            }
            foreach ($last_three_months as $date) {
                $month_key = $date['year'] . '-' . $date['month'];
                $income = isset($monthly_reports[$month_key]['income']) ? $monthly_reports[$month_key]['income'] : 0;
                $expense = isset($monthly_reports[$month_key]['expense']) ? $monthly_reports[$month_key]['expense'] : 0;
                ?>
                <div>
                    <h3><?php echo $months[$date['month']] . ' ' . $date['year']; ?></h3>
                    <p>Gelir: <span class="amount"><?php echo $income; ?> TL</span></p>
                    <p>Gider: <span class="amount expense"><?php echo $expense; ?> TL</span></p>
                </div>
                <?php
            }
            ?>
        </div>

        <h2>Yaklaşan Ödemeler</h2>
        <ul>
            <?php foreach ($upcoming_payments as $payment) : ?>
                <li>
                    <span><?php echo $payment['client_name']; ?></span>
                    <span class="amount"><?php echo $payment['payment_amount']; ?> TL</span>
                    <span class="date"><?php echo $payment['payment_date']; ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <h2>Çalışan Maaş ve Prim Yönetimi</h2>
        <div class="report-summary">
            <div>
                <h3>Ödenmemiş Maaşlar</h3>
                <ul>
                    <?php foreach ($unpaid_salaries as $salary) : ?>
                        <li>
                            <span><?php echo $salary['employee_name']; ?></span>
                            <span class="amount"><?php echo $salary['salary_amount']; ?> TL</span>
                            <span class="date"><?php echo $salary['payment_date']; ?></span>
                            <form method="post" action="">
                                <input type="hidden" name="salary_id" value="<?php echo $salary['id']; ?>">
                                <input type="hidden" name="payment_amount" value="<?php echo $salary['salary_amount']; ?>">
                                <button type="submit" name="pay_salary">Öde</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h3>Ödenmiş Maaşlar</h3>
                <ul>
                    <?php foreach ($paid_salaries as $salary) : ?>
                        <li>
                            <span><?php echo $salary['employee_name']; ?></span>
                            <span class="amount"><?php echo $salary['salary_amount']; ?> TL</span>
                            <span class="date"><?php echo $salary['payment_date']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

</body>
</html>
</html>