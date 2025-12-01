<?php
// ---------------- CONFIG - update these ----------------
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'hospital_db';
// -------------------------------------------------------

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// ---------- Add patient (prepared statement) ----------
$alert = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $age = intval($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');

    if ($fullname === '') {
        $alert = "Full name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO patients (fullname, age, gender, address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $fullname, $age, $gender, $address);
        if ($stmt->execute()) {
            $alert = "Patient added successfully.";
        } else {
            $alert = "Error adding patient.";
        }
        $stmt->close();
    }
}

// ---------- Search handling ----------
$search = trim($_GET['q'] ?? '');

// ---------- Counts ----------
$total_patients = 0;
$res = $conn->query("SELECT COUNT(*) AS total FROM patients");
if ($res) {
    $row = $res->fetch_assoc();
    $total_patients = (int)$row['total'];
    $res->free();
}

// try to count doctors (if table exists)
$total_doctors = null;
$res2 = $conn->query("SELECT COUNT(*) AS total FROM doctors");
if ($res2) {
    $row2 = $res2->fetch_assoc();
    $total_doctors = (int)$row2['total'];
    $res2->free();
}

// ---------- Fetch patients (with optional search) ----------
if ($search !== '') {
    $like = "%{$search}%";
    $stmt = $conn->prepare("SELECT * FROM patients WHERE fullname LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $patients = $stmt->get_result();
} else {
    $patients = $conn->query("SELECT * FROM patients ORDER BY created_at DESC");
}

?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Patients — CityHealth Center</title>

<style>
  :root{
    --green:#2e8b57;
    --green-2:#3aa06a;
    --muted:#f4f7f6;
    --card:#ffffff;
    --text:#333;
    --accent:#0b7a4d;
    --glass: rgba(255,255,255,0.85);
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
    background: linear-gradient(180deg, #f3faf6 0%, #eef8f1 100%);
    color:var(--text);
  }

  /* layout */
  .app {
    display:grid;
    grid-template-columns: 260px 1fr;
    min-height:100vh;
  }

  /* sidebar */
  .sidebar{
    background:linear-gradient(180deg,var(--green),var(--green-2));
    color:#fff;
    padding:22px 18px;
    display:flex;
    flex-direction:column;
    gap:18px;
  }
  .brand{
    display:flex;
    gap:12px;
    align-items:center;
    margin-bottom:6px;
  }
  .logo-mark{
    width:48px;height:48px;border-radius:10px;
    background:rgba(255,255,255,0.12);
    display:grid;place-items:center;
    font-weight:700;
    font-size:20px;
    box-shadow: 0 6px 18px rgba(6,27,12,0.12) inset;
  }
  .brand h1{font-size:18px;
    
    margin:0;
    letter-spacing:0.2px}
  .brand p{margin:0;
    font-size:12px;
    opacity:0.95}

  .nav { display:flex; 
    flex-direction:column; 
    gap:8px; 
    margin-top:6px; }
  .nav a {
    display:flex;
     gap:12px; 
     align-items:center;
    padding:10px;
    border-radius:
    8px;text-decoration:none;
    color:inherit;
    font-weight:600; opacity:0.95;
  }
  .nav a:hover { 
    background: rgba(255,255,255,0.08); }

  .nav svg{width:18px;
    height:18px
    ;opacity:0.95}

  /* header + main */
  .main-wrap{padding:18px}
  .header {
    display:flex; 
    align-items:center; 
    justify-content:space-between;
    gap:12px; margin-bottom:18px;
  }
  .site-title {
    display:flex; 
    gap:14px;
     align-items:center;
  }
  .text-logo{
    font-weight:700; 
    font-size:20px; 
    color:var(--accent);
  }
  .search-box{
    display:flex;
     gap:8px; 
     align-items:center;
  }
  .search-box input{
    padding:8px 12px;
    border-radius:8px;
    border:1px solid #e6efe9;
    background:white;
    min-width:260px;
  }
  .card-row{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px,1fr));
    gap:14px;
  }

  .card{
    background:var(--card); border-radius:12px; padding:18px;
    box-shadow: 0 6px 18px rgba(12,35,15,0.06);
  }
  .big-num{font-size:28px;font-weight:800;color:var(--green-2)}
  .muted{color:#6b776f;font-weight:600;font-size:13px}

  .layout{
    margin-top:18px;
    display:grid;
    grid-template-columns: 380px 1fr;
    gap:18px;
  }

  /* form */
  .form .row{display:flex;gap:10px}
  label{display:block;font-weight:700;font-size:13px;margin-top:8px}
  input[type="text"], input[type="number"], select, textarea {
    width:100%; padding:10px;border-radius:8px;border:1px solid #e6efe9;background:white;
    margin-top:6px; font-size:14px;
  }
  button.primary {
    margin-top:12px;padding:10px 14px;border-radius:10px;border:none;background:var(--green-2);
    color:white;font-weight:700;cursor:pointer;
  }
  .note {font-size:13px;color:#517056;margin-top:8px}

  /* table */
  .table-wrap { background:var(--card); border-radius:12px; padding:12px; box-shadow: 0 6px 18px rgba(12,35,15,0.06); }
  table { width:100%; border-collapse:collapse; font-size:14px; }
  th, td { text-align:left; padding:10px; border-bottom:1px solid #eef4ea; }
  th { background:linear-gradient(90deg, rgba(46,139,87,0.06), rgba(58,160,106,0.02)); font-weight:700; color:var(--accent) }
  tr:hover td { background: rgba(14,43,24,0.02); }

  .small { font-size:12px; color:#6e776f; }

  /* responsive */
  @media (max-width:900px){
    .app{grid-template-columns:1fr}
    .sidebar{flex-direction:row;gap:12px;padding:12px;overflow:auto}
    .layout{grid-template-columns:1fr}
    .main-wrap{padding:12px}
  }

</style>
</head>
<body>
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="brand">
        <div class="logo-mark">CH</div>
        <div>
          <h1>CityHealth</h1>
          <p>Center Dashboard</p>
        </div>
      </div>

      <nav class="nav" aria-label="Main navigation">
        <a href="#" title="Dashboard">
          <svg viewBox="0 0 24 24" fill="none"><path d="M3 13h8V3H3v10zM3 21h8v-6H3v6zM13 21h8V11h-8v10zM13 3v6h8V3h-8z" fill="#fff"/></svg>
          Dashboard
        </a>
        <a href="#" style="background:rgba(255,255,255,0.08);">
          <svg viewBox="0 0 24 24" fill="none"><path d="M20 6H4v2h16V6zM20 11H4v2h16v-2zM4 16h10v2H4v-2z" fill="#fff"/></svg>
          Patients
        </a>
        <a href="#">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4zM4 20v-1c0-2.21 3.58-4 8-4s8 1.79 8 4v1H4z" fill="#fff"/></svg>
          Staff
        </a>
        <a href="#">
          <svg viewBox="0 0 24 24" fill="none"><path d="M12 2L2 7l10 5 10-5-10-5zm0 13L2 10v7l10 5 10-5v-7l-10 5z" fill="#fff"/></svg>
          Reports
        </a>
      </nav>

      <div style="margin-top:auto;font-size:13px;opacity:0.95">
        <div class="small">Signed in as</div>
        <div style="font-weight:700;margin-top:6px">Admin</div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main-wrap">
      <header class="header">
        <div class="site-title">
          <div style="display:flex;flex-direction:column">
            <div class="text-logo">CityHealth Center</div>
            <div class="small">Patient management</div>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:12px">
          <form class="search-box" method="get" action="">
            <input type="text" name="q" placeholder="Search patient name..." value="<?php echo htmlspecialchars($search); ?>" />
            <button type="submit" style="padding:8px 10px;border-radius:8px;border:none;background:var(--green);color:white;font-weight:700">Search</button>
            <?php if ($search !== ''): ?>
              <a href="patients.php" style="margin-left:8px;font-size:13px;color:#496e54;text-decoration:none">clear</a>
            <?php endif; ?>
          </form>
        </div>
      </header>

      <!-- stats row -->
      <section class="card-row">
        <div class="card">
          <div class="muted">Total Patients</div>
          <div class="big-num"><?php echo number_format($total_patients); ?></div>
          <div class="note">All registered patients in the system</div>
        </div>

        <div class="card">
          <div class="muted">Total Doctors</div>
          <div class="big-num"><?php echo $total_doctors === null ? 'N/A' : number_format($total_doctors); ?></div>
          <div class="note">Shows N/A if no doctors table exists</div>
        </div>

        <div class="card">
          <div class="muted">Recent Activity</div>
          <div style="margin-top:8px" class="small">Add patients quickly using the form. Table shows newest first.</div>
        </div>
      </section>

      <div class="layout">
        <!-- left: add patient -->
        <aside class="card form">
          <h3 style="margin:0 0 8px 0;">Add New Patient</h3>
          <?php if($alert): ?>
            <div style="padding:10px;border-radius:8px;background:#eef9ef;color:#225a2e;margin-bottom:10px"><?php echo htmlspecialchars($alert); ?></div>
          <?php endif; ?>
          <form method="post" action="">
            <label>Full name</label>
            <input type="text" name="fullname" required>

            <div class="row">
              <div style="flex:1">
                <label>Age</label>
                <input type="number" name="age" min="0" step="1">
              </div>
              <div style="width:140px">
                <label>Gender</label>
                <select name="gender">
                  <option value="">Select</option>
                  <option>Male</option>
                  <option>Female</option>
                  <option>Other</option>
                </select>
              </div>
            </div>

            <label>Address</label>
            <input type="text" name="address">

            <button class="primary" type="submit" name="add_patient">Add Patient</button>
            <div class="note">We use prepared statements for secure insertion.</div>
          </form>
        </aside>

        <!-- right: table -->
        <section>
          <div class="table-wrap">
            <h3 style="margin-top:0">Patients List <?php if($search!==""){ echo " — search: " . htmlspecialchars($search); } ?></h3>

            <table>
              <thead>
                <tr>
                  <th style="width:60px">#</th>
                  <th>Full name</th>
                  <th style="width:80px">Age</th>
                  <th style="width:110px">Gender</th>
                  <th>Address</th>
                  <th style="width:150px">Registered</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($patients && $patients->num_rows > 0): ?>
                  <?php while ($r = $patients->fetch_assoc()): ?>
                    <tr>
                      <td><?php echo (int)$r['id']; ?></td>
                      <td><?php echo htmlspecialchars($r['fullname']); ?></td>
                      <td><?php echo htmlspecialchars($r['age']); ?></td>
                      <td><?php echo htmlspecialchars($r['gender']); ?></td>
                      <td><?php echo htmlspecialchars($r['address']); ?></td>
                      <td class="small"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" style="padding:20px;text-align:center">No patients found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

    </main>
  </div>

<?php
// close statements / connection
if (isset($stmt) && $stmt instanceof mysqli_stmt) $stmt->close();
$conn->close();
?>
</body>
</html>
