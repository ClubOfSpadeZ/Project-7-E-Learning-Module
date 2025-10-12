<?php
// Replace SQLite database operations with WordPress database operations

// Ensure the WordPress table exists

use Automattic\WooCommerce\GoogleListingsAndAds\DB\Table;

function whfr_ensure_schema_wp()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'license_registrations';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        first_name TEXT,
        surname TEXT,
        work_title TEXT,
        organisation TEXT,
        email TEXT,
        work_phone TEXT,
        mobile TEXT,
        comments TEXT,
        license_code TEXT,
        invoice_sent TINYINT(1) DEFAULT 0,
        invoice_amount FLOAT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_paid TINYINT(1) DEFAULT 0,
        payment_link TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'whfr_ensure_schema_wp');

// Generate sequential license codes
function whfr_make_license_code_wp()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'license_registrations';
    $result = $wpdb->get_var("SELECT MAX(CAST(SUBSTRING(license_code, 3) AS UNSIGNED)) FROM $table_name");
    $next_num = (int) $result + 1;

    return 'MH' . str_pad((string) $next_num, 4, '0', STR_PAD_LEFT);
}

// Save registration data to the WordPress database instead of a file
function handle_registration_form_wp()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'license_registrations';

    // Collect fields safely
    $firstName    = sanitize_text_field($_POST['firstName'] ?? '');
    $surname      = sanitize_text_field($_POST['surname'] ?? '');
    $workTitle    = sanitize_text_field($_POST['workTitle'] ?? '');
    $organisation = sanitize_text_field($_POST['organisation'] ?? '');
    $email        = sanitize_email($_POST['email'] ?? '');
    $workPhone    = sanitize_text_field($_POST['workPhone'] ?? '');
    $mobile       = sanitize_text_field($_POST['mobile'] ?? '');
    $comments     = sanitize_textarea_field($_POST['comments'] ?? '');

    // Generate license code
    $licenseCode = whfr_make_license_code_wp();

    // Insert data into the WordPress database
    $result = $wpdb->insert(
        $table_name,
        [
            'first_name'    => $firstName,
            'surname'       => $surname,
            'work_title'    => $workTitle,
            'organisation'  => $organisation,
            'email'         => $email,
            'work_phone'    => $workPhone,
            'mobile'        => $mobile,
            'comments'      => $comments,
            'license_code'  => $licenseCode,
            'invoice_sent'  => 0,
            'invoice_amount' => 0,
            'created_at'    => current_time('mysql', 1),
        ],
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%f',
            '%s'
        ]
    );

    if ($result === false) {
        error_log('Database Insert Error: ' . $wpdb->last_error);
        wp_die('There was an error saving your registration. Please try again.');
    }

    wp_redirect(home_url('/thank-you/'));
    exit;
}
add_action('admin_post_nopriv_send_registration_form', 'handle_registration_form_wp');
add_action('admin_post_send_registration_form', 'handle_registration_form_wp');


/** ===== ORG PORTAL — PLACE 1: session + constants + CSRF ===== */

add_action('init', function () {
    // Ensure the session save path is set and writable
    if (!is_dir('/home/web_user') || !is_writable('/home/web_user')) {
        // Fallback to the default PHP session save path or a custom writable directory
        session_save_path(sys_get_temp_dir()); // Use system's temp directory
    }

    // Start the session
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_strict_mode', '1');
        session_name('lic_portal');
        session_start();
    }
});

if (!defined('WHFR_DB_FILE')) {
    // same DB file you already use in handle_registration_form()
    define('WHFR_DB_FILE', WP_CONTENT_DIR . '/uploads/licenses.db');
}

// CSRF helpers for login
function whfr_csrf_token(): string
{
    if (empty($_SESSION['whfr_csrf'])) {
        $_SESSION['whfr_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['whfr_csrf'];
}
function whfr_csrf_check($t): bool
{
    return isset($_SESSION['whfr_csrf']) && hash_equals($_SESSION['whfr_csrf'], (string)$t);
}

// Adding shortcode to print hidden CSRF input INSIDE your raw HTML form
add_shortcode('whfr_login_csrf', function () {
    return '<input type="hidden" name="whfr_csrf" value="' . esc_attr(whfr_csrf_token()) . '">';
});

// Adding shortcode to show login warnings based on ?e= flag
add_shortcode('whfr_login_notice', function () {
    $e = isset($_GET['e']) ? sanitize_text_field($_GET['e']) : '';
    if (!$e) return '';
    $msg = '';
    if ($e === 'bad')   $msg = 'Invalid Organisation Name or License Code. Please try again.';
    if ($e === 'empty') $msg = 'Please enter both Organisation Name and License Code.';
    if ($e === 'csrf')  $msg = 'Security check failed. Please refresh the page and try again.';
    if (!$msg) return '';

    return '<div style="margin:10px 0; padding:10px; border-radius:8px; background:#ffecec; border:1px solid #ffc7c7; color:#a40000;">'
        . esc_html($msg)
        . '</div>';
});

/** ===== STRIPE settings in functions.php ===== */
if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', 'sk_test_51S7Q4EF2jpSvqU2x6BcyR2JvdFBfUn8yCfI8dTWLQb6WM8nn5rtrBVpQM30PC8Gc6az6wciXhVHMDqZhlzWvuFdJ00vhNZOVzS'); // TODO: replace with your Stripe Secret key
}
if (!defined('STRIPE_SUCCESS_URL')) {
    define('STRIPE_SUCCESS_URL', home_url("/completed-payment/")); // make this page in Step 5
}
if (!defined('STRIPE_CANCEL_URL')) {
    define('STRIPE_CANCEL_URL',  home_url("/org-dashboard/")); // your portal page
}

//  Child theme styles
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');
function my_theme_enqueue_styles()
{
    $parenthandle = 'vantage-parent';
    $theme        = wp_get_theme();

    wp_enqueue_style(
        $parenthandle,
        get_template_directory_uri() . '/style.css',
        [],
        $theme->parent()->get('Version')
    );

    wp_enqueue_style(
        'custom-style',
        get_stylesheet_uri(),
        [$parenthandle],
        $theme->get('Version')
    );
}



// Hide pages from WP search
function tg_exclude_pages_from_search_results($query)
{
    if ($query->is_main_query() && $query->is_search() && ! is_admin()) {
        $query->set('post_type', ['post']);
    }
}
add_action('pre_get_posts', 'tg_exclude_pages_from_search_results');



// Log mail failures to wp-content/debug.log
// (enable WP_DEBUG_LOG in wp-config.php to see them)
add_action('wp_mail_failed', function ($error) {
    error_log('wp_mail_failed: ' . $error->get_error_message());
});



// Improving the deliverability
add_action('phpmailer_init', function ($phpmailer) {
    $site_host  = parse_url(home_url(), PHP_URL_HOST);
    $from_email = 'no-reply@' . $site_host; // e.g. no-reply@workhealthandfitnessrecord.com.au

    if (empty($phpmailer->Sender)) {
        $phpmailer->Sender = $from_email;
    }
});


// Registration form to send emai notification to the admin and sending the data to sqlite
// the registration form posting to /wp-admin/admin-post.php?action=send_registration_form
add_action('admin_post_nopriv_send_registration_form', 'handle_registration_form');
add_action('admin_post_send_registration_form',        'handle_registration_form');


// making sure that the table exists and required columns are present.
function whfr_ensure_schema(SQLite3 $db): void
{
    // Create table if missing
    $db->exec("
        CREATE TABLE IF NOT EXISTS license_registrations (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            first_name      TEXT,
            surname         TEXT,
            work_title      TEXT,
            organisation    TEXT,
            email           TEXT,
            work_phone      TEXT,
            mobile          TEXT,
            comments        TEXT,
            license_code    TEXT,
            invoice_sent    INTEGER DEFAULT 0,   -- 0/1 persisted 'sent' state
            invoice_amount  REAL    DEFAULT 0,   -- saved invoice amount
            created_at      DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Backfill columns if DB is older
    $have_license = false;
    $have_sent    = false;
    $have_amount  = false;

    $ti = $db->query("PRAGMA table_info('license_registrations')");
    while ($row = $ti->fetchArray(SQLITE3_ASSOC)) {
        $col = isset($row['name']) ? strtolower($row['name']) : '';
        if ($col === 'license_code')   $have_license = true;
        if ($col === 'invoice_sent')   $have_sent    = true;
        if ($col === 'invoice_amount') $have_amount  = true;
    }

    if (! $have_license) $db->exec("ALTER TABLE license_registrations ADD COLUMN license_code TEXT");
    if (! $have_sent)    $db->exec("ALTER TABLE license_registrations ADD COLUMN invoice_sent INTEGER DEFAULT 0");
    if (! $have_amount)  $db->exec("ALTER TABLE license_registrations ADD COLUMN invoice_amount REAL DEFAULT 0");

    // NEW: payment state + link
    $have_paid = false;
    $have_link = false;

    $ti = $db->query("PRAGMA table_info('license_registrations')");
    while ($row = $ti->fetchArray(SQLITE3_ASSOC)) {
        $col = strtolower($row['name'] ?? '');
        if ($col === 'is_paid')      $have_paid = true;
        if ($col === 'payment_link') $have_link = true;
    }

    if (!$have_paid) $db->exec("ALTER TABLE license_registrations ADD COLUMN is_paid INTEGER DEFAULT 0");
    if (!$have_link) $db->exec("ALTER TABLE license_registrations ADD COLUMN payment_link TEXT");
}


// Generating sequential license codes like MH0001, MH000 for each organisation
function whfr_make_license_code(String $table): string
{
    global $wpdb;

    // take max numeric part after 'MH'
    $result   = $wpdb->get_var("SELECT MAX(CAST(SUBSTR(license_code, 3) AS INTEGER)) FROM $table");
    $next_num = (int) $result + 1;

    return 'MH' . str_pad((string) $next_num, 4, '0', STR_PAD_LEFT);
}


function handle_registration_form()
{
    // Collect fields safely
    $firstName    = sanitize_text_field($_POST['firstName'] ?? '');
    $surname      = sanitize_text_field($_POST['surname'] ?? '');
    $workTitle    = sanitize_text_field($_POST['workTitle'] ?? '');
    $organisation = sanitize_text_field($_POST['organisation'] ?? '');
    $email        = sanitize_email($_POST['email'] ?? '');
    $work_phone   = sanitize_text_field($_POST['work_phone'] ?? '');
    $mobile       = sanitize_text_field($_POST['mobile'] ?? '');
    $comments     = sanitize_textarea_field($_POST['comments'] ?? '');

    try {
        global $wpdb;

        $dbtable = $wpdb->prefix . 'license_registrations';

        // Ensuring that the table exists and the license_code column as well exists
        // whfr_ensure_schema($database);

        // Generate sequential license code
        $license_code = whfr_make_license_code($dbtable);

        // Insert row
        $stmt = $wpdb->insert(
            $dbtable,
            [
                'first_name'    => $firstName,
                'surname'       => $surname,
                'work_title'    => $workTitle,
                'organisation'  => $organisation,
                'email'         => $email,
                'work_phone'    => $work_phone,
                'mobile'        => $mobile,
                'comments'      => $comments,
                'license_code'  => $license_code,
                'invoice_sent'  => 0,
                'invoice_amount' => 0,
                'created_at'    => current_time('mysql', 1),
            ],
            [
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%f',
                '%s'
            ]
        );

        // If the preparation of the database gets failed, we are enidng the process here.
        if (! $stmt) {
            error_log('WHFR prepare failed: Invalid database object or operation.');
            wp_die('Database error (prepare). Please try again later.');
        }
    } catch (Throwable $e) {
        error_log("SQLite insert failed: " . $e->getMessage());
        wp_die('Database error. Please try again later.');
    }

    // Email to admin about the new license interest
    $to       = 'aravindrjv285@gmail.com';
    // $to    = 'john.miller@millerhealth.com.au';
    $subject  = 'New Licensing Organization Interest';
    $message  =
        "New registration application\n\n" .
        "Yes, we are interested. Please contact us.\n\n" .
        "First Name: {$firstName}\n" .
        "Surname: {$surname}\n" .
        "Work Title: {$workTitle}\n" .
        "Organisation: {$organisation}\n" .
        "Email: {$email}\n" .
        "Work Phone: {$work_phone}\n" .
        "Mobile: {$mobile}\n" .
        // "License Code: {$license_code}\n" .
        "Comments:\n{$comments}\n";

    $site_host  = parse_url(home_url(), PHP_URL_HOST);
    $from_email = 'no-reply@' . $site_host;

    $headers = [
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <' . $from_email . '>',
    ];
    if ($email) {
        $headers[] = 'Reply-To: ' . $email;
    }

    $sent = wp_mail($to, $subject, $message, $headers);

    if ($sent) {
        wp_safe_redirect(get_permalink(649));  // thank-you page
    } else {
        $back = wp_get_referer() ?: home_url('/');
        $back = add_query_arg('form', 'error', $back);
        wp_safe_redirect($back);
    }
    exit;
}



// Send invoice (HTML) and PERSIST state/amount in SQLite
add_action('admin_post_send_invoice',        'handle_send_invoice');
add_action('admin_post_nopriv_send_invoice', 'handle_send_invoice');

function handle_send_invoice()
{
    // Get & sanitize the details for the invoice
    $org_id   = sanitize_text_field($_POST['org_id']   ?? '');
    $org_name = sanitize_text_field($_POST['org_name'] ?? '');
    $email_to = sanitize_email($_POST['email']         ?? '');
    $amount   = number_format((float) ($_POST['amount'] ?? 0), 2, '.', '');
    $license  = sanitize_text_field($_POST['license']  ?? '');
    $row_id   = isset($_POST['row_id']) ? (int) $_POST['row_id'] : 0; // <-- numeric DB id

    if (! $email_to || ! is_email($email_to) || $amount <= 0) {
        wp_die('Invalid request.');
    }

    // header/footers
    $currency = 'AUD';
    $site     = get_bloginfo('name');
    $site_url = home_url();
    $from     = get_option('admin_email');

    // building invoice number and date
    $year  = date('Y');
    $seq   = (int) get_option('soi_seq_' . $year, 0) + 1;
    update_option('soi_seq_' . $year, $seq);
    $inv_no = sprintf('INV-%s-%04d', $year, $seq);
    $date   = date_i18n(get_option('date_format'));

    // Subject for the email
    $subject = "Invoice {$inv_no} — for {$org_name}";

    // HTML body for the email layout
    $html = '
    <div style="max-width:720px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;color:#222">
      <div style="border:1px solid #eee;border-radius:12px;padding:20px">
        <h2 style="margin:0 0 6px 0">Invoice ' . $inv_no . '</h2>
        <div style="color:#666;margin-bottom:12px">Date: ' . $date . '</div>
		<h3 style="margin:0 0 6px 0">Please login to the account with the license code given below to proceed with the payment.</h3>
        <table style="width:100%;border-collapse:collapse;margin-bottom:14px">
          <tr>
            <td style="width:50%;vertical-align:top;padding:8px;border:1px solid #f0f0f0">
              <strong>From</strong><br>' . $site . ' (' . $site_url . ')<br>' . esc_html($from) . '
            </td>
            <td style="width:50%;vertical-align:top;padding:8px;border:1px solid #f0f0f0">
              <strong>Bill To</strong><br>' . esc_html($org_name) . '<br>
              ' . ($org_id ? 'Org ID: ' . esc_html($org_id) . '<br>' : '') . '
              ' . ($license ? 'License Code: ' . esc_html($license) . '<br>' : '') . '
              ' . esc_html($email_to) . '
            </td>
          </tr>
        </table>
		
        <table style="width:100%;border-collapse:collapse">
          <thead>
            <tr>
              <th style="text-align:left;border-bottom:2px solid #ddd;padding:8px">Description</th>
              <th style="text-align:right;border-bottom:2px solid #ddd;padding:8px">Amount</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:8px;border-bottom:1px solid #eee">License Fee</td>
              <td style="padding:8px;border-bottom:1px solid #eee;text-align:right">' . $currency . ' ' . number_format((float) $amount, 2) . '</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td style="padding:8px;text-align:right"><strong>Total</strong></td>
              <td style="padding:8px;text-align:right"><strong>' . $currency . ' ' . number_format((float) $amount, 2) . '</strong></td>
            </tr>
          </tfoot>
        </table>

      </div>
    </div>';

    // Send as HTML
    add_filter('wp_mail_content_type', function () {
        return 'text/html';
    });

    $site_host  = parse_url(home_url(), PHP_URL_HOST);
    $from_email = 'no-reply@' . $site_host;

    $headers = [
        'From: ' . $site . ' <' . $from_email . '>',
        'Reply-To: ' . $from,
    ];

    $sent = wp_mail($email_to, $subject, $html, $headers);

    remove_filter('wp_mail_content_type', function () {
        return 'text/html';
    });

    // here we add this code to make sure it persists the state of those invoices which are already sent as sent itself.
    try {
        global $wpdb;
        $dbtable = $wpdb->prefix . 'license_registrations';

        if ($dbtable && $row_id > 0 && $amount > 0) {

            whfr_ensure_schema_wp();

            $stmt = $wpdb->prepare("
                UPDATE $dbtable
                   SET invoice_sent   = 1,
                       invoice_amount = $amount
                 WHERE id = $row_id
            ");

            $result = $wpdb->query($stmt);
            if ($result === false) {
                error_log('WHFR execute failed: ' . $wpdb->last_error);
            }

        }
    } catch (Throwable $e) {
        error_log('Persist invoice state failed: ' . $e->getMessage());
    }

    // Redirect back (no need to carry state in URL anymore)
    if ($sent) {
        wp_safe_redirect(wp_get_referer());
    } else {
        wp_safe_redirect(add_query_arg('invoice', 'error', wp_get_referer()));
    }
    exit;
}



// AJAX: return <tr> rows from wp-content/uploads/licenses.db
// Used by your admin table page.
add_action('wp_ajax_get_license_rows',        'whfr_get_license_rows');
add_action('wp_ajax_nopriv_get_license_rows', 'whfr_get_license_rows');

function whfr_get_license_rows()
{
    global $wpdb;

    $dbtable = $wpdb->prefix . 'license_registrations';
    if (! $wpdb->get_var("SHOW TABLES LIKE '$dbtable'")) {
        echo '<tr><td colspan="8"><strong>Table not found:</strong> ' . esc_html($dbtable) . '</td></tr>';
        wp_die();
    }

    try {
        whfr_ensure_schema_wp();

        $sql = "
            SELECT id,
                   organisation,
                   email,
                   work_phone,
                   mobile,
                   license_code,
                   COALESCE(invoice_sent,  0) AS invoice_sent,
                   COALESCE(invoice_amount,0) AS invoice_amount,
				   COALESCE(is_paid, 0) AS is_paid
              FROM $dbtable
          ORDER BY id DESC
        ";
        $results = $wpdb->get_results($sql);

        ob_start();
        if (!empty($results)) {
            foreach ($results as $r) {
                $id       = (int) $r->id;
                $org_id   = 'ORG-' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
                $org_name = (string) ($r->organisation ?? '');
                $email    = (string) ($r->email ?? '');
                $phone    = (string) ($r->work_phone ?? '');
                $mobile   = (string) ($r->mobile ?? '');
                $code     = (string) ($r->license_code ?? '');
                $sent     = (int) $r->invoice_sent === 1;
                $amt      = (float) $r->invoice_amount;
                $paid     = (int) $r->is_paid === 1;
?>
                <tr>
                    <td><input type="text" value="<?php echo esc_attr($org_id); ?>" readonly></td>
                    <td><input type="text" value="<?php echo esc_attr($org_name); ?>" readonly></td>
                    <td><input type="tel" value="<?php echo esc_attr($phone); ?>" readonly></td>
                    <td><input type="tel" value="<?php echo esc_attr($mobile); ?>" readonly></td>
                    <td><input type="email" value="<?php echo esc_attr($email); ?>" readonly></td>
                    <td><input type="text" value="<?php echo esc_attr($code ?: 'MH0000'); ?>" readonly></td>

                    <td>
                        <div class="pricing-input">
                            <span class="currency">$</span>
                            <input
                                type="number"
                                class="price"
                                min="0"
                                step="0.01"
                                value="<?php echo $amt > 0 ? esc_attr(number_format($amt, 2, '.', '')) : ''; ?>"
                                <?php echo $sent ? 'disabled' : ''; ?>
                                placeholder="0.00">
                        </div>
                    </td>
                    <td>
                        <form action="/wp-admin/admin-post.php" method="POST" class="invoiceForm">
                            <input type="hidden" name="action" value="send_invoice">
                            <input type="hidden" name="row_id" value="<?php echo $id; ?>"><!-- numeric DB id -->
                            <input type="hidden" name="org_id" value="<?php echo esc_attr($org_id); ?>">
                            <input type="hidden" name="org_name" value="<?php echo esc_attr($org_name); ?>">
                            <input type="hidden" name="email" value="<?php echo esc_attr($email); ?>">
                            <input type="hidden" name="license" value="<?php echo esc_attr($code); ?>">
                            <input type="hidden" name="amount" class="hiddenAmount" value="">
                            <?php if ($paid): ?>
                                <button class="btn disabled" disabled>✅ Payment Successful</button>
                            <?php elseif ($sent): ?>
                                <button class="btn disabled" disabled>Invoice Sent</button>
                            <?php else: ?>
                                <button type="submit" class="btn sendBtn">Send Invoice</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
    <?php
            }
        }
        echo ob_get_clean();
    } catch (Throwable $e) {
        echo '<tr><td colspan="8">error: ' . esc_html($e->getMessage()) . '</td></tr>';
    }

    wp_die();
}

/** ===== ORG PORTAL — PLACE 3: login handler ===== */

add_action('admin_post_nopriv_whfr_login', 'whfr_handle_org_login');
add_action('admin_post_whfr_login',        'whfr_handle_org_login');

function whfr_handle_org_login()
{
    // must come from our form, with CSRF
    if (($_POST['whfr_login_submit'] ?? '') !== '1' || !whfr_csrf_check($_POST['whfr_csrf'] ?? '')) {
        wp_safe_redirect(add_query_arg('e', 'csrf', wp_get_referer() ?: home_url('/')));
        exit;
    }

    $org  = sanitize_text_field($_POST['orgName'] ?? '');
    $code = (string)($_POST['licenseCode'] ?? '');

    if ($org === '' || $code === '') {
        wp_safe_redirect(add_query_arg('e', 'empty', wp_get_referer() ?: home_url('/')));
        exit;
    }

    if (!file_exists(WHFR_DB_FILE)) {
        wp_die('licenses.db not found.');
    }

    global $wpdb;
    $dbtable = $wpdb->prefix . 'license_registrations';

    whfr_ensure_schema_wp();

    // latest matching registration for that org + code
    $stmt = $wpdb->prepare("
    SELECT id, organisation, email,
           COALESCE(invoice_sent,0)   AS invoice_sent,
           COALESCE(invoice_amount,0) AS invoice_amount,
           COALESCE(is_paid,0)        AS is_paid,
           COALESCE(payment_link,'')  AS payment_link
      FROM $dbtable
     WHERE organisation = $org AND license_code = $code
  ORDER BY id DESC
     LIMIT 1
  ");
    if (!$stmt) {
        wp_die('DB error (prepare).');
    }

    $results = $wpdb->get_results($stmt, ARRAY_A);

    if (empty($results)) {
        // redirect back with warning flag
        wp_safe_redirect(add_query_arg('e', 'bad', wp_get_referer() ?: home_url('/')));
        exit;
    }

    // success: set session for portal
    $row = $results[0]; // Assign the first result to $row
    session_regenerate_id(true);
    $_SESSION['lic_org_id']       = (int)$row['id'];
    $_SESSION['lic_org_name']     = (string)$row['organisation'];
    $_SESSION['lic_invoice_sent'] = (int)$row['invoice_sent'];
    $_SESSION['lic_invoice_amt']  = (float)$row['invoice_amount'];
    $_SESSION['lic_is_paid']      = (int)$row['is_paid'];
    $_SESSION['lic_pay_link']     = (string)$row['payment_link'];

    // Decide redirect based on payment status
    $program_url = home_url('/program-copy/'); // Dynamically get the domain

    if ((int)$row['is_paid'] === 1) {
        // already paid: go straight to program page
        wp_safe_redirect(esc_url_raw($program_url));
    } else {
        // not paid yet: go to Org Dashboard (your portal page)
        // If you use a fixed page ID, keep get_permalink(957). If you use a slug, use home_url('/org-dashboard/')
        wp_safe_redirect(get_permalink(957));
        // or: wp_safe_redirect( home_url('/org-dashboard/') );
    }
    exit;
}

/** ===== ORG PORTAL — PLACE 4: dashboard (Logout removed) ===== */

function whfr_require_login()
{
    if (empty($_SESSION['lic_org_id'])) {
        wp_safe_redirect(home_url('/org-login/'));
        exit;
    }
}

add_shortcode('org_dashboard', function () {
    whfr_require_login();

    // refresh from DB (admin may have updated payment link/paid)
    $db = new SQLite3(WHFR_DB_FILE);
    $db->busyTimeout(3000);
    whfr_ensure_schema($db);

    $id  = (int)$_SESSION['lic_org_id'];
    $res = $db->query("
    SELECT COALESCE(invoice_sent,0)   AS invoice_sent,
           COALESCE(invoice_amount,0) AS invoice_amount,
           COALESCE(is_paid,0)        AS is_paid,
           COALESCE(payment_link,'')  AS payment_link
      FROM license_registrations
     WHERE id = $id
     LIMIT 1
  ");
    if ($r = $res->fetchArray(SQLITE3_ASSOC)) {
        $_SESSION['lic_invoice_sent'] = (int)$r['invoice_sent'];
        $_SESSION['lic_invoice_amt']  = (float)$r['invoice_amount'];
        $_SESSION['lic_is_paid']      = (int)$r['is_paid'];
        $_SESSION['lic_pay_link']     = (string)$r['payment_link'];
    }
    $db->close();

    $org  = esc_html($_SESSION['lic_org_name']);
    $sent = (int)($_SESSION['lic_invoice_sent'] ?? 0);
    $amt  = (float)($_SESSION['lic_invoice_amt'] ?? 0);
    $paid = (int)($_SESSION['lic_is_paid'] ?? 0);
    $link = (string)($_SESSION['lic_pay_link'] ?? '');

    ob_start(); ?>
    <div style="max-width:820px;margin:40px auto;font-family:Arial,sans-serif;">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <h2 style="margin:0">Welcome, <?php echo $org; ?></h2>
            <!-- Logout button removed as requested -->
        </div>

        <div style="margin-top:16px;padding:16px;border:1px solid #eee;border-radius:12px;background:#fff">
            <?php if ($amt > 0): ?>
                <p><strong>Amount due:</strong> AUD <?php echo number_format($amt, 2); ?>
                    — <?php echo $sent ? 'Invoice sent' : 'Invoice pending'; ?></p>
            <?php else: ?>
                <p>The team will get in touch with you through mail or phone call</p>
            <?php endif; ?>

            <?php if ($paid): ?>
                <div style="margin-top:10px;padding:12px;border-radius:8px;background:#ecfff0;border:1px solid #c7e8c7;">
                    Payment confirmed. Your downloads are unlocked.
                </div>
                <!-- TODO: swap this for a real download list -->
                <p style="margin-top:12px;"><a href="#" class="btn">Download Books</a></p>
            <?php else: // not paid 
            ?>
                <?php if ($amt > 0): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php?action=whfr_create_checkout')); ?>" style="margin-top:12px;">
                        <input type="hidden" name="whfr_csrf" value="<?php echo esc_attr(whfr_csrf_token()); ?>">
                        <button type="submit"
                            style="text-decoration:none;padding:10px 14px;border-radius:8px;background:#0d47a1;color:#fff;font-weight:600;border:0;cursor:pointer;">
                            Pay now
                        </button>
                    </form>
                    <p style="color:#555">You’ll be redirected to a secure checkout to complete payment.</p>

                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div>
<?php
    return ob_get_clean();
});

// Keep the logout handler (not shown on UI now)
add_action('admin_post_whfr_logout', function () {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    wp_safe_redirect(home_url('/org-login/'));
    exit;
});

// Put this helper near your other helpers
function whfr_load_stripe_sdk()
{
    static $loaded = false;
    if ($loaded) return;

    $candidates = [
        ABSPATH . 'vendor/autoload.php',                   // Composer
        WP_PLUGIN_DIR . '/stripe-php/init.php',            // plugins/stripe-php
        WP_CONTENT_DIR . '/plugins/stripe-php/init.php' // mu-plugins/stripe-php
    ];

    foreach ($candidates as $file) {
        if (file_exists($file)) {
            require_once $file;
            $loaded = true;
            return;
        }
    }

    error_log('Stripe SDK not found. Checked: ' . implode(' | ', $candidates));
    wp_die('Stripe SDK not found. Please upload stripe-php to wp-content/plugins/stripe-php/');
}


/** ===== ORG PORTAL — PLACE 5: create Stripe Checkout on demand ===== */
// add_action('admin_post_nopriv_whfr_create_checkout', 'whfr_create_checkout');
// add_action('admin_post_whfr_create_checkout',        'whfr_create_checkout');

// function whfr_create_checkout() {
//   // Must be logged in & CSRF protected
//   if (empty($_SESSION['lic_org_id'])) {  
//     wp_safe_redirect(home_url('/org-login/')); exit;
//   }
//   if (!isset($_POST['whfr_csrf']) || !whfr_csrf_check($_POST['whfr_csrf'])) {
//     wp_safe_redirect(add_query_arg('e','csrf', home_url('/org-portal/'))); exit;
//   }

//   // Load latest org row (price, email)
//   if (!file_exists(WHFR_DB_FILE)) wp_die('licenses.db not found.');
//   $db = new SQLite3(WHFR_DB_FILE);
//   $db->busyTimeout(3000);
//   whfr_ensure_schema($db);

//   $id  = (int)$_SESSION['lic_org_id'];
//   $res = $db->query("
//     SELECT id, organisation, email,
//            COALESCE(invoice_amount,0) AS invoice_amount,
//            COALESCE(is_paid,0)        AS is_paid
//       FROM license_registrations
//      WHERE id = $id
//      LIMIT 1
//   ");
//   $row = $res ? $res->fetchArray(SQLITE3_ASSOC) : null;
//   $db->close();

//   if (!$row) { wp_safe_redirect(home_url('/org-portal/')); exit; }
//   if ((int)$row['is_paid'] === 1) { wp_safe_redirect(home_url('/org-portal/')); exit; }

//   $amount = (float)$row['invoice_amount'];
//   if ($amount <= 0) { wp_safe_redirect(add_query_arg('e','noamount', home_url('/org-portal/'))); exit; }

//   // Include Stripe SDK (choose ONE path automatically)
//   if (defined('ABSPATH') && file_exists(ABSPATH . 'vendor/autoload.php')) {
//     require_once ABSPATH . 'vendor/autoload.php'; // Composer
//   } else {
//     require_once WP_CONTENT_DIR . '/plugins/stripe-php/init.php'; // Manual
//   }

//   \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

//   try {
//     $session = \Stripe\Checkout\Session::create([
//       'mode' => 'payment',
//       'customer_email' => $row['email'] ?: null,
//       'line_items' => [[
//         'price_data' => [
//           'currency' => 'aud',
//           'product_data' => ['name' => 'License Fee — ' . ($row['organisation'] ?? 'Organisation')],
//           'unit_amount' => (int) round($amount * 100), // cents
//         ],
//         'quantity' => 1,
//       ]],
//       'metadata' => [
//         'lic_row_id'   => (string)$row['id'],
//         'organisation' => (string)$row['organisation'],
//       ],
//       'success_url' => STRIPE_SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
//       'cancel_url'  => STRIPE_CANCEL_URL,
//     ]);

//     wp_redirect($session->url);
//     exit;

//   } catch (Exception $e) {
//     error_log('Stripe checkout create failed: ' . $e->getMessage());
//     wp_safe_redirect(add_query_arg('e','stripe', home_url('/org-portal/'))); exit;
//   }
// }

/** ===== ORG PORTAL — PLACE 5: Simple Stripe Checkout Integration ===== */
add_action('admin_post_nopriv_whfr_create_checkout', 'whfr_create_checkout');
add_action('admin_post_whfr_create_checkout',        'whfr_create_checkout');

function whfr_create_checkout()
{
    if (empty($_SESSION['lic_org_id'])) {
        wp_safe_redirect(home_url('/org-login/'));
        exit;
    }

    if (!file_exists(WHFR_DB_FILE)) {
        wp_die('licenses.db not found.');
    }

    $db  = new SQLite3(WHFR_DB_FILE);
    $id  = (int) $_SESSION['lic_org_id'];
    $row = $db->querySingle("SELECT organisation, email, invoice_amount FROM license_registrations WHERE id = $id LIMIT 1", true);
    $db->close();

    if (!$row) {
        wp_safe_redirect(home_url('/org-dashboard/'));
        exit;
    }

    $amount = (float) $row['invoice_amount'];
    if ($amount <= 0) {
        wp_safe_redirect(home_url('/org-dashboard/?e=noamount'));
        exit;
    }

    // Load Stripe SDK
    if (file_exists(WP_CONTENT_DIR . '/plugins/stripe-php/init.php')) {
        require_once WP_CONTENT_DIR . '/plugins/stripe-php/init.php';
    }

    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    try {
        $session = \Stripe\Checkout\Session::create([
            'mode' => 'payment',
            'customer_email' => $row['email'] ?: null,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'aud',
                    'product_data' => ['name' => 'License Fee — ' . ($row['organisation'] ?? 'Organisation')],
                    'unit_amount' => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'metadata' => ['lic_row_id' => (string)$id],
            'success_url' => STRIPE_SUCCESS_URL . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => STRIPE_CANCEL_URL,
        ]);

        wp_redirect($session->url);
        exit;
    } catch (Exception $e) {
        error_log('Stripe error: ' . $e->getMessage());
        wp_safe_redirect(home_url('/org-dashboard/?e=stripe'));
        exit;
    }
}

/** Making changes in the licenses.db file and the admin new page if the payment is successful*/
add_action('init', function () {
    if (isset($_GET['session_id']) && strpos($_SERVER['REQUEST_URI'], '/completed-payment') !== false) {
        if (!class_exists('\Stripe\Stripe')) {
            require_once WP_CONTENT_DIR . '/plugins/stripe-php/init.php';
        }

        \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
        try {
            $session = \Stripe\Checkout\Session::retrieve(sanitize_text_field($_GET['session_id']));
            if ($session && $session->payment_status === 'paid') {
                $row_id = isset($session->metadata->lic_row_id) ? (int)$session->metadata->lic_row_id : 0;
                if ($row_id > 0 && file_exists(WHFR_DB_FILE)) {
                    $db = new SQLite3(WHFR_DB_FILE);
                    $db->exec("UPDATE license_registrations SET is_paid = 1 WHERE id = $row_id");
                    $db->close();
                }
            }
        } catch (Exception $e) {
            error_log('Stripe success update failed: ' . $e->getMessage());
        }
    }
});


// Download FULL rendered page HTML
// Usage: /wp-admin/admin-post.php?action=download_full_page_html&id=123
add_action('admin_post_download_full_page_html', function () {
    if (! is_user_logged_in() || ! current_user_can('edit_pages')) {
        wp_die('Not allowed.');
    }

    $id  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $url = get_permalink($id);
    if (! $url) wp_die('Page not found.');

    $resp = wp_remote_get($url);
    if (is_wp_error($resp)) wp_die('Loopback failed: ' . esc_html($resp->get_error_message()));

    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="page-' . $id . '-full.html"');

    echo wp_remote_retrieve_body($resp);
    exit;
});
