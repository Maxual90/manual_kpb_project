<?php

session_start();

if (!isset($_SESSION['admin_no'])) {
    header("Location: login.html");
    exit();
}

$logged_in_admin_no = $_SESSION['admin_no'];


// =========================
// DATABASE CONNECTION
// =========================

$host = "localhost";
$user = "root";
$password = "";
$database = "firstdb";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}


// =========================
// GET POSTS FROM ACCEPTED CONNECTIONS
// =========================

$sql = "
    SELECT
        posts.id,
        posts.content,
        posts.created_at,
        students.name,
        students.course,
        students.batch

    FROM posts

    INNER JOIN students
        ON posts.admin_no = students.admin_no

    INNER JOIN connections
        ON (
            (
                connections.sender_admin_no = ?
                AND connections.receiver_admin_no = posts.admin_no
            )
            OR
            (
                connections.receiver_admin_no = ?
                AND connections.sender_admin_no = posts.admin_no
            )
        )

    WHERE connections.status = 'accepted'
      AND posts.admin_no != ?

    ORDER BY posts.created_at DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iii",
    $logged_in_admin_no,
    $logged_in_admin_no,
    $logged_in_admin_no
);

$stmt->execute();

$result = $stmt->get_result();


// =========================
// GET PEOPLE TO CONNECT WITH
// =========================

$people_sql = "
    SELECT
        admin_no,
        name,
        course,
        batch

    FROM students

    WHERE admin_no != ?

    AND admin_no NOT IN (

        SELECT
            CASE
                WHEN sender_admin_no = ?
                THEN receiver_admin_no
                ELSE sender_admin_no
            END

        FROM connections

        WHERE sender_admin_no = ?
           OR receiver_admin_no = ?
    )

    ORDER BY name
";

$people_stmt = $conn->prepare($people_sql);

$people_stmt->bind_param(
    "iiii",
    $logged_in_admin_no,
    $logged_in_admin_no,
    $logged_in_admin_no,
    $logged_in_admin_no
);

$people_stmt->execute();

$people_result = $people_stmt->get_result();

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Alumni Connect</title>


<style>

/* =========================
   RESET
========================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial, Helvetica, sans-serif;
}


/* =========================
   BODY
========================= */

body{
    background:#f4f7fb;
    color:#333;
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

    box-shadow:0 2px 8px rgba(0,0,0,.2);
}


.logo{

    font-size:25px;

    font-weight:bold;
}


/* =========================
   NAVIGATION
========================= */

.nav{

    display:flex;

    gap:10px;
}


.nav a{

    color:white;

    text-decoration:none;

    padding:11px 18px;

    border-radius:6px;

    font-size:15px;

    transition:.3s;
}


.nav a:hover{

    background:#00508f;
}


.nav .active{

    background:#ff9800;
}


/* =========================
   MAIN 60 / 40
========================= */

.main{

    width:95%;

    max-width:1400px;

    margin:30px auto;

    display:grid;

    grid-template-columns:60% 40%;

    gap:25px;
}


/* =========================
   LEFT FEED
========================= */

.feed-section{

    min-width:0;
}


.feed-title{

    color:#003366;

    font-size:24px;

    margin-bottom:20px;
}


/* =========================
   POST CARD
========================= */

.post-card{

    background:white;

    border-radius:12px;

    padding:22px;

    margin-bottom:20px;

    box-shadow:0 4px 14px rgba(0,0,0,.08);
}


.post-header{

    display:flex;

    align-items:center;

    margin-bottom:15px;
}


.profile-circle{

    width:48px;

    height:48px;

    border-radius:50%;

    background:#003366;

    color:white;

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:bold;

    font-size:18px;

    margin-right:12px;
}


.user-details h3{

    color:#003366;

    font-size:18px;

    margin-bottom:4px;
}


.user-details p{

    color:#777;

    font-size:13px;
}


.post-content{

    font-size:16px;

    line-height:1.6;

    color:#333;

    padding:5px 0 10px 0;
}


.post-time{

    color:#999;

    font-size:12px;

    border-top:1px solid #eee;

    padding-top:12px;
}


/* =========================
   RIGHT SIDE
========================= */

.right-section{

    background:white;

    border-radius:12px;

    padding:25px;

    box-shadow:0 4px 14px rgba(0,0,0,.08);

    height:max-content;

    max-height:calc(100vh - 130px);

    overflow-y:auto;
}


.right-section h2{

    color:#003366;

    margin-bottom:20px;

    font-size:22px;
}


/* =========================
   PEOPLE CARD
========================= */

.person-card{

    padding:15px 0;

    border-bottom:1px solid #eee;
}


.person-card:last-child{

    border-bottom:none;
}


.person-info{

    display:flex;

    align-items:center;

    margin-bottom:12px;
}


.person-circle{

    width:42px;

    height:42px;

    background:#003366;

    color:white;

    border-radius:50%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:bold;

    margin-right:10px;

    flex-shrink:0;
}


.person-info h3{

    color:#003366;

    font-size:15px;

    margin-bottom:4px;
}


.person-info p{

    color:#777;

    font-size:12px;
}


/* =========================
   CONNECT BUTTON
========================= */

.connect-btn{

    width:100%;

    padding:9px;

    border:none;

    border-radius:6px;

    background:#007bff;

    color:white;

    cursor:pointer;

    font-size:14px;

    transition:.3s;
}


.connect-btn:hover{

    background:#0056b3;
}


/* =========================
   EMPTY STATES
========================= */

.empty-feed{

    background:white;

    padding:40px;

    border-radius:12px;

    text-align:center;

    box-shadow:0 4px 14px rgba(0,0,0,.08);
}


.empty-feed h3{

    color:#003366;

    margin-bottom:10px;
}


.empty-feed p{

    color:#777;

    line-height:1.5;
}


.no-people{

    color:#777;

    text-align:center;

    padding:20px 0;
}


/* =========================
   MOBILE
========================= */

@media(max-width:800px){

    header{

        padding:15px 20px;

        height:auto;

        flex-direction:column;

        gap:15px;
    }


    .nav{

        width:100%;

        justify-content:center;

        flex-wrap:wrap;
    }


    .main{

        grid-template-columns:1fr;

    }


    .right-section{

        max-height:none;

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

        <a href="messages.php">
            Message
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
     MAIN
========================= -->

<div class="main">


    <!-- =====================
         60% FEED
    ====================== -->

    <section class="feed-section">

        <h2 class="feed-title">
            Your Alumni Feed
        </h2>


        <?php

        if ($result->num_rows > 0) {

            while ($row = $result->fetch_assoc()) {

                $first_letter = strtoupper(
                    substr($row['name'], 0, 1)
                );

        ?>


        <!-- POST -->

        <div class="post-card">


            <div class="post-header">


                <div class="profile-circle">

                    <?php

                    echo htmlspecialchars(
                        $first_letter
                    );

                    ?>

                </div>


                <div class="user-details">

                    <h3>

                        <?php

                        echo htmlspecialchars(
                            $row['name']
                        );

                        ?>

                    </h3>


                    <p>

                        <?php

                        echo htmlspecialchars(
                            $row['course']
                        );

                        ?>

                        |

                        Batch

                        <?php

                        echo htmlspecialchars(
                            $row['batch']
                        );

                        ?>

                    </p>

                </div>

            </div>


            <div class="post-content">

                <?php

                echo nl2br(
                    htmlspecialchars(
                        $row['content']
                    )
                );

                ?>

            </div>


            <div class="post-time">

                <?php

                echo htmlspecialchars(
                    $row['created_at']
                );

                ?>

            </div>


        </div>


        <?php

            }

        } else {

        ?>


        <div class="empty-feed">

            <h3>
                Your feed is empty
            </h3>

            <p>
                Posts from your accepted connections
                will appear here.
            </p>

        </div>


        <?php

        }

        ?>

    </section>



    <!-- =====================
         40% CONNECTIONS
    ====================== -->

    <aside class="right-section">

        <h2>
            Connect With Alumni
        </h2>


        <?php

        if ($people_result->num_rows > 0) {

            while ($person = $people_result->fetch_assoc()) {

                $letter = strtoupper(
                    substr($person['name'], 0, 1)
                );

        ?>


        <div class="person-card">


            <div class="person-info">


                <div class="person-circle">

                    <?php

                    echo htmlspecialchars(
                        $letter
                    );

                    ?>

                </div>


                <div>

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

                </div>

            </div>


            <form
                action="send_request.php"
                method="POST"
            >

                <input
                    type="hidden"
                    name="receiver_admin_no"
                    value="<?php
                        echo htmlspecialchars(
                            $person['admin_no']
                        );
                    ?>"
                >


                <button
                    type="submit"
                    class="connect-btn"
                >
                    Connect
                </button>

            </form>


        </div>


        <?php

            }

        } else {

        ?>


        <p class="no-people">
            No new alumni to connect with.
        </p>


        <?php

        }

        ?>

    </aside>


</div>


</body>

</html>


<?php

$stmt->close();

$people_stmt->close();

$conn->close();

?>