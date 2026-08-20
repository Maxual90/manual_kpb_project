<?php

session_start();


// =====================================================
// CHECK LOGIN
// =====================================================

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$my_admin_no = (int)$_SESSION['admin_no'];


// =====================================================
// DATABASE CONNECTION
// =====================================================

$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


// =====================================================
// SEND MESSAGE
// =====================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $receiver = isset($_POST['receiver'])
        ? (int)$_POST['receiver']
        : 0;

    $message = isset($_POST['message'])
        ? trim($_POST['message'])
        : "";


    // Basic validation
    if ($receiver <= 0) {
        die("Invalid receiver.");
    }

    if ($message === "") {
        die("Message cannot be empty.");
    }


    // -------------------------------------------------
    // CHECK THAT USERS ARE ACCEPTED CONNECTIONS
    // -------------------------------------------------

    $check_sql = "
        SELECT id
        FROM connections
        WHERE status = 'accepted'
        AND (
            (
                sender_admin_no = ?
                AND receiver_admin_no = ?
            )
            OR
            (
                sender_admin_no = ?
                AND receiver_admin_no = ?
            )
        )
        LIMIT 1
    ";

    $check_stmt = $conn->prepare($check_sql);

    if (!$check_stmt) {
        die("Connection check prepare failed: " . $conn->error);
    }

    $check_stmt->bind_param(
        "iiii",
        $my_admin_no,
        $receiver,
        $receiver,
        $my_admin_no
    );

    if (!$check_stmt->execute()) {
        die("Connection check failed: " . $check_stmt->error);
    }

    $check_result = $check_stmt->get_result();


    if ($check_result->num_rows == 0) {

        $check_stmt->close();

        die("You can only message an accepted connection.");
    }

    $check_stmt->close();


    // -------------------------------------------------
    // INSERT MESSAGE
    // -------------------------------------------------

    $insert_sql = "
        INSERT INTO messages
        (
            sender_admin_no,
            receiver_admin_no,
            message
        )
        VALUES (?, ?, ?)
    ";

    $insert_stmt = $conn->prepare($insert_sql);

    if (!$insert_stmt) {
        die("Message prepare failed: " . $conn->error);
    }

    $insert_stmt->bind_param(
        "iis",
        $my_admin_no,
        $receiver,
        $message
    );


    if (!$insert_stmt->execute()) {

        die(
            "Message insert failed: "
            . $insert_stmt->error
        );
    }

    $insert_stmt->close();


    // Go back to conversation
    header(
        "Location: messages.php?user=" . $receiver
    );

    exit();
}


// =====================================================
// GET ACCEPTED CONNECTIONS
// =====================================================

$contacts_sql = "
    SELECT
        s.admin_no,
        s.name,
        s.course,
        s.batch

    FROM connections c

    INNER JOIN students s
        ON s.admin_no =
        CASE
            WHEN c.sender_admin_no = ?
            THEN c.receiver_admin_no
            ELSE c.sender_admin_no
        END

    WHERE
        (
            c.sender_admin_no = ?
            OR
            c.receiver_admin_no = ?
        )

        AND c.status = 'accepted'

    ORDER BY s.name ASC
";

$contacts_stmt = $conn->prepare($contacts_sql);

if (!$contacts_stmt) {
    die("Contacts query failed: " . $conn->error);
}

$contacts_stmt->bind_param(
    "iii",
    $my_admin_no,
    $my_admin_no,
    $my_admin_no
);

if (!$contacts_stmt->execute()) {
    die("Contacts execute failed: " . $contacts_stmt->error);
}

$contacts_result = $contacts_stmt->get_result();


// =====================================================
// GET SELECTED USER
// =====================================================

$selected_user = null;

if (isset($_GET['user'])) {

    $selected_id = (int)$_GET['user'];

    if ($selected_id > 0) {

        $selected_sql = "
            SELECT
                s.admin_no,
                s.name,
                s.course,
                s.batch

            FROM connections c

            INNER JOIN students s
                ON s.admin_no =
                CASE
                    WHEN c.sender_admin_no = ?
                    THEN c.receiver_admin_no
                    ELSE c.sender_admin_no
                END

            WHERE
                (
                    c.sender_admin_no = ?
                    OR
                    c.receiver_admin_no = ?
                )

                AND c.status = 'accepted'

                AND s.admin_no = ?

            LIMIT 1
        ";

        $selected_stmt = $conn->prepare($selected_sql);

        if (!$selected_stmt) {
            die("Selected user query failed: " . $conn->error);
        }

        $selected_stmt->bind_param(
            "iiii",
            $my_admin_no,
            $my_admin_no,
            $my_admin_no,
            $selected_id
        );

        if (!$selected_stmt->execute()) {
            die("Selected user execute failed: " . $selected_stmt->error);
        }

        $selected_result = $selected_stmt->get_result();

        if ($selected_result->num_rows == 1) {
            $selected_user = $selected_result->fetch_assoc();
        }

        $selected_stmt->close();
    }
}


// =====================================================
// GET MESSAGES
// =====================================================

$messages_result = null;

if ($selected_user !== null) {

    $other_admin_no = (int)$selected_user['admin_no'];

    $messages_sql = "
        SELECT
            id,
            sender_admin_no,
            receiver_admin_no,
            message,
            created_at

        FROM messages

        WHERE
            (
                sender_admin_no = ?
                AND receiver_admin_no = ?
            )

            OR

            (
                sender_admin_no = ?
                AND receiver_admin_no = ?
            )

        ORDER BY created_at ASC, id ASC
    ";

    $messages_stmt = $conn->prepare($messages_sql);

    if (!$messages_stmt) {
        die("Messages query failed: " . $conn->error);
    }

    $messages_stmt->bind_param(
        "iiii",
        $my_admin_no,
        $other_admin_no,
        $other_admin_no,
        $my_admin_no
    );

    if (!$messages_stmt->execute()) {
        die("Messages execute failed: " . $messages_stmt->error);
    }

    $messages_result = $messages_stmt->get_result();
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Messages | Alumni Connect</title>


<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}

body{
    background:#f4f7fb;
}


/* =========================
   HEADER
========================= */

header{
    height:75px;

    background:#003366;

    color:white;

    display:flex;

    align-items:center;

    justify-content:space-between;

    padding:0 40px;
}

.logo{
    font-size:25px;
    font-weight:bold;
}

.nav{
    display:flex;
    gap:10px;
}

.nav a{
    color:white;
    text-decoration:none;

    padding:10px 18px;

    border-radius:6px;

    font-size:15px;
}

.nav a:hover{
    background:#00508f;
}


/* =========================
   MAIN
========================= */

.container{
    width:95%;
    max-width:1100px;

    height:calc(100vh - 115px);

    margin:20px auto;

    display:grid;

    grid-template-columns:35% 65%;

    background:white;

    border-radius:12px;

    overflow:hidden;

    box-shadow:
        0 4px 15px rgba(0,0,0,.1);
}


/* =========================
   CONTACTS
========================= */

.contacts{
    border-right:1px solid #ddd;

    overflow-y:auto;
}

.contacts-title{
    padding:20px;

    color:#003366;

    border-bottom:1px solid #eee;

    font-size:21px;
}

.contact{
    display:block;

    padding:17px 20px;

    text-decoration:none;

    border-bottom:1px solid #eee;

    color:#333;
}

.contact:hover{
    background:#f1f5f9;
}

.contact.active{
    background:#e8f1f8;
}

.contact h3{
    color:#003366;

    font-size:16px;

    margin-bottom:5px;
}

.contact p{
    color:#777;

    font-size:13px;
}


/* =========================
   CHAT
========================= */

.chat{
    height:100%;

    display:flex;

    flex-direction:column;
}


/* CHAT HEADER */

.chat-header{
    padding:18px 22px;

    border-bottom:1px solid #ddd;

    background:#fafafa;
}

.chat-header h2{
    color:#003366;

    font-size:19px;
}

.chat-header p{
    color:#777;

    font-size:13px;

    margin-top:4px;
}


/* =========================
   MESSAGE AREA
========================= */

.messages{
    flex:1;

    padding:25px;

    overflow-y:auto;

    background:#f4f7fb;
}

.message{
    max-width:65%;

    padding:11px 15px;

    border-radius:12px;

    margin-bottom:12px;

    line-height:1.5;

    font-size:14px;

    word-wrap:break-word;
}

.sent{
    background:#003366;

    color:white;

    margin-left:auto;

    border-bottom-right-radius:3px;
}

.received{
    background:white;

    color:#333;

    border-bottom-left-radius:3px;

    box-shadow:
        0 2px 5px rgba(0,0,0,.08);
}

.time{
    display:block;

    font-size:10px;

    margin-top:5px;

    opacity:.65;
}


/* =========================
   MESSAGE FORM
========================= */

.message-form{
    display:flex;

    gap:10px;

    padding:15px;

    border-top:1px solid #ddd;

    background:white;
}

.message-form input{
    flex:1;

    padding:12px;

    border:1px solid #ccc;

    border-radius:6px;

    outline:none;

    font-size:14px;
}

.message-form input:focus{
    border-color:#007bff;
}

.message-form button{
    padding:12px 22px;

    border:none;

    border-radius:6px;

    background:#ff9800;

    color:white;

    cursor:pointer;

    font-weight:bold;
}

.message-form button:hover{
    background:#e68900;
}


/* =========================
   EMPTY CHAT
========================= */

.empty{
    height:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    text-align:center;

    padding:30px;

    color:#777;
}

.empty h2{
    color:#003366;

    margin-bottom:10px;
}


/* =========================
   MOBILE
========================= */

@media(max-width:700px){

    header{
        height:auto;

        padding:15px;

        flex-direction:column;

        gap:10px;
    }

    .container{
        grid-template-columns:1fr;

        height:auto;
    }

    .contacts{
        max-height:300px;

        border-right:none;

        border-bottom:1px solid #ddd;
    }

    .chat{
        min-height:500px;
    }

}

</style>

</head>


<body>


<!-- =========================
     HEADER
========================= -->

<header>

    <div class="logo">
        🎓 Alumni Connect
    </div>

    <nav class="nav">

        <a href="feed.php">
            Feed
        </a>

        <a href="connection_requests.php">
            Connection Requests
        </a>

        <a href="profile.php">
            Profile
        </a>

    </nav>

</header>



<!-- =========================
     MAIN CONTAINER
========================= -->

<div class="container">


    <!-- =====================
         CONTACT LIST
    ====================== -->

    <div class="contacts">

        <h2 class="contacts-title">
            Messages
        </h2>


        <?php if ($contacts_result->num_rows > 0): ?>


            <?php while ($person = $contacts_result->fetch_assoc()): ?>

                <?php

                $active = false;

                if ($selected_user !== null) {

                    $active =
                        ((int)$selected_user['admin_no']
                        ===
                        (int)$person['admin_no']);
                }

                ?>


                <a
                    href="messages.php?user=<?php
                        echo (int)$person['admin_no'];
                    ?>"
                    class="contact <?php
                        echo $active ? 'active' : '';
                    ?>"
                >

                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $person['name']
                        );
                        ?>
                    </h3>

                    <p>

                        <?php
                        echo htmlspecialchars(
                            $person['course']
                        );
                        ?>

                        |

                        Batch

                        <?php
                        echo htmlspecialchars(
                            $person['batch']
                        );
                        ?>

                    </p>

                </a>


            <?php endwhile; ?>


        <?php else: ?>


            <div style="
                padding:20px;
                color:#777;
            ">

                No accepted connections yet.

            </div>


        <?php endif; ?>

    </div>



    <!-- =====================
         CHAT SECTION
    ====================== -->

    <div class="chat">


        <?php if ($selected_user !== null): ?>


            <!-- CHAT HEADER -->

            <div class="chat-header">

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $selected_user['name']
                    );
                    ?>

                </h2>

                <p>

                    <?php
                    echo htmlspecialchars(
                        $selected_user['course']
                    );
                    ?>

                    |

                    Batch

                    <?php
                    echo htmlspecialchars(
                        $selected_user['batch']
                    );
                    ?>

                </p>

            </div>



            <!-- MESSAGES -->

            <div class="messages" id="messages">


                <?php if (
                    $messages_result !== null &&
                    $messages_result->num_rows > 0
                ): ?>


                    <?php while (
                        $msg =
                        $messages_result->fetch_assoc()
                    ): ?>


                        <?php

                        $is_sent =
                            ((int)$msg['sender_admin_no']
                            ===
                            $my_admin_no);

                        $message_class =
                            $is_sent
                            ? "sent"
                            : "received";

                        ?>


                        <div class="
                            message
                            <?php echo $message_class; ?>
                        ">

                            <?php

                            echo nl2br(
                                htmlspecialchars(
                                    $msg['message']
                                )
                            );

                            ?>


                            <span class="time">

                                <?php

                                echo htmlspecialchars(
                                    $msg['created_at']
                                );

                                ?>

                            </span>

                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div style="
                        text-align:center;
                        color:#999;
                        padding:30px;
                    ">

                        No messages yet.<br>

                        Start the conversation!

                    </div>


                <?php endif; ?>


            </div>



            <!-- SEND MESSAGE -->

            <form
                class="message-form"
                method="POST"
                action="messages.php?user=<?php
                    echo (int)$selected_user['admin_no'];
                ?>"
            >

                <input
                    type="hidden"
                    name="receiver"
                    value="<?php
                        echo (int)$selected_user['admin_no'];
                    ?>"
                >


                <input
                    type="text"
                    name="message"
                    placeholder="Type a message..."
                    autocomplete="off"
                    maxlength="5000"
                    required
                >


                <button type="submit">
                    Send
                </button>

            </form>


        <?php else: ?>


            <div class="empty">

                <div>

                    <h2>
                        Select a conversation
                    </h2>

                    <p>
                        Choose an accepted connection
                        from the left to start messaging.
                    </p>

                </div>

            </div>


        <?php endif; ?>


    </div>

</div>


<script>

// Scroll chat to the newest message

const messages =
    document.getElementById("messages");

if (messages) {

    messages.scrollTop =
        messages.scrollHeight;
}

</script>


</body>

</html>


<?php

$contacts_stmt->close();

if (isset($messages_stmt)) {
    $messages_stmt->close();
}

$conn->close();

?>