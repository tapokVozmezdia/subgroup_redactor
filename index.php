<!DOCTYPE html>

<html>
    <head>

        <link rel="stylesheet" href="site.css">
        <link rel="stylesheet" href="ssel.css">
        <link rel="stylesheet" href="mid.css">
        <link rel="stylesheet" href="scon.css">

        <?php
            function display_query($query, $student_links=false) {
                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic 
                    user=postgres
                    password=postgres
                ");
                
                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $result = pg_query($connection, $query);
                if (!$result) {
                    echo '<script type="text/javascript">alert("sql query failed")</script>';
                    exit;
                }

                echo "
                    <table>
                    <tr>
                ";

                $num_fields = pg_num_fields($result);

                for ($i = 0; $i < $num_fields; $i++)
                {
                    $field_name = pg_field_name($result, $i);
                    if ($field_name == "first_name")
                    {
                        $field_name = "Имя";
                    }
                    if ($field_name == "middle_name")
                    {
                        $field_name = "Фамилия";
                    }
                    if ($field_name == "last_name")
                    {
                        $field_name = "Отчество";
                    }

                    echo "
                    <th class = 'column'>
                        $field_name
                    </th>
                    ";
                }
                
                echo "
                    </tr>
                ";

                while($row = pg_fetch_row($result)) {

                    echo "<tr>";

                    for ($j = 0; $j < $num_fields; $j++)
                    {
                        echo "<th>";
                            
                        if ($student_links) {

                            $r = http_build_query($_GET);

                                $q = $_GET;
                                // replace parameter(s)
                                $q["student"] = $row[0];
                                // rebuild url
                                $r = http_build_query($q);

                            $lnk = $_SERVER['PHP_SELF'] . "?" . $r;

                            echo "
                                <div class='student_entry'>
                                <a 
                                    class='student_link' 
                                    href = $lnk
                                >
                            ";
                            // такая функция с параметром это 0-ой смертный грех в библии, 
                            // т.к. ее логика сильно меняется при students_links=true,
                            // но это самое простое решение, которое я смог придумать
                        }

                           echo "$row[$j]";

                        if ($student_links) {
                            echo "
                                </a>
                                </div>
                            ";
                        }

                        echo "</th>";
                    }

                    echo "</tr>";

                }
                echo "
                    </table>
                ";
            }
        ?>

        <?php
            function display_table($table_name) {
               display_query("SELECT * FROM $table_name;");
            }
        ?>

        <?php
            function display_student($student_id) {
                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic 
                    user=postgres
                    password=postgres
                ");
                
                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $result = pg_query($connection, "SELECT * FROM students 
                    WHERE id = $student_id;");
                if (!$result) {
                    echo '<script type="text/javascript">alert("sql query failed")</script>';
                    exit;
                }

                $row = pg_fetch_row($result);
                
                for ($j = 0; $j < count($row); $j++){

                    $field_name = pg_field_name($result, $j);

                    echo "
                        <div class='student_field_entry'>
                    ";

                    if ($row[$j]) {
                        echo "
                            <div class = 'student_field_prompt'>
                                $field_name:
                            </div>
                        ";
                    }

                    echo "
                        <div class = 'student_field'>
                            $row[$j]
                        </div>
                    ";

                    echo "
                        </div>
                    ";

                }
            }
        ?>

        <?php
            function display_groups() {
                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic 
                    user=postgres
                    password=postgres
                ");
                
                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $result = pg_query($connection, "SELECT DISTINCT group_id FROM students_to_groups
                    ORDER BY group_id");
                if (!$result) {
                    echo '<script type="text/javascript">alert("sql query failed")</script>';
                    exit;
                }

                while($row = pg_fetch_row($result)) {
                    echo "
                        <div class = 'my_button'>
                            <a 
                            href = 'index.php?group_selected=true&group=$row[0]'>
                                $row[0]
                            </a>
                        </div>
                    ";
                }
            }
        ?>

        <?php
            function display_group() {

                if (!isset($_GET['group_selected'])) {
                    echo '<script type="text/javascript">alert("bad request")</script>';
                    exit;
                }

                $i = $_GET["group"];
                display_query(
                    "SELECT s1.id, s1.first_name, s1.middle_name, s1.last_name, s2.subgroup FROM students as s1 
                    JOIN students_to_subgroups as s2
                    ON s1.id = s2.student_id
                    WHERE s1.id IN (
                        SELECT student_id FROM students_to_groups 
                        WHERE group_id = $i
                    ) ORDER BY middle_name;"
                );
            }
        ?>

        <?php
            function display_group_sg() {

                if (!isset($_GET['group_selected'])) {
                    echo '<script type="text/javascript">alert("bad request")</script>';
                    exit;
                }

                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic
                    user=postgres
                    password=postgres
                ");

                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $i = $_GET["group"];

                $result = pg_query($connection, 
                "WITH T1 AS (SELECT s1.id, s1.first_name, s1.middle_name, s1.last_name, s2.subgroup 
                FROM students as s1 
                JOIN students_to_subgroups as s2
                ON s1.id = s2.student_id
                WHERE s1.id IN (
                    SELECT student_id FROM students_to_groups 
                    WHERE group_id = $i
                ) ORDER BY middle_name)

                SELECT max(subgroup) FROM T1;"
                );
                if (!$result) {
                    echo '<script type="text/javascript">alert("sql query failed")</script>';
                    exit;
                }

                $max_sg = pg_fetch_row($result)[0];

                echo '<div class = "table_block">';

                for ($j = 1; $j <= $max_sg; $j++) {

                    echo "
                    <div class = 'sg_table'>
                    <div class = 'prompt'>
                        Подгруппа №$j
                    </div>
                    ";

                    display_query(
                        "WITH T1 AS (SELECT s1.id, s1.first_name, s1.middle_name, s1.last_name, s2.subgroup FROM students as s1 
                        JOIN students_to_subgroups as s2
                        ON s1.id = s2.student_id
                        WHERE s1.id IN (
                            SELECT student_id FROM students_to_groups 
                            WHERE group_id = $i
                        ) ORDER BY middle_name)
                        
                        SELECT id, 
                        middle_name, 
                        first_name, 
                        last_name 
                        FROM T1
                        WHERE subgroup = $j;",

                        true
                    );
                    
                    echo "
                    </div>
                    ";
                
                }

                echo '
                    </div>
                ';

            }
        ?>

        <?php
            function display_controls() {

                if (!isset($_GET["student"]) || !isset($_GET["group"])) {
                    return;
                }
                
                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic 
                    user=postgres
                    password=postgres
                ");
                
                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $group_num = $_GET["group"];

                $result = pg_query($connection, 
                    "WITH T1 AS (SELECT s1.id, s1.first_name, s1.middle_name, s1.last_name, s2.subgroup 
                    FROM students as s1 
                    JOIN students_to_subgroups as s2
                    ON s1.id = s2.student_id
                    WHERE s1.id IN (
                        SELECT student_id FROM students_to_groups 
                        WHERE group_id = $group_num
                    ) ORDER BY middle_name)

                SELECT max(subgroup) FROM T1;"
                );
                
                if (!$result) {
                    echo '<script type="text/javascript">alert("sql query failed")</script>';
                    exit;
                }

                $sg_num = pg_fetch_row($result)[0];

                $clink = $_SERVER['PHP_SELF'] . "?" 
                . http_build_query($_GET);

                for($i=1;$i<=$sg_num;$i++) {

                    $tlink = $clink . "&move_to=$i";

                    echo "
                        <div class='control_button'>
                            <a href = $tlink>
                                move to subgroup $i
                            </a>
                        </div>
                    ";
                }

                $tlink = $clink . "&move_to=-1";

                echo "
                        <div class='control_button'>
                            <a href = $tlink>
                                move to a new subgroup
                            </a>
                        </div>
                ";

            }
        ?>

        <?php
            function move_func() {

                if (!isset($_GET["student"]) 
                    || !isset($_GET["move_to"])
                    || !isset($_GET["group"])) {
                    echo '<script type="text/javascript">alert("student cannot be moved")</script>';
                    exit;
                }

                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic 
                    user=postgres
                    password=postgres
                ");
                
                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $group_num = $_GET["group"];

                $sg = pg_query($connection, 
                    "WITH T1 AS (SELECT s1.id, s1.first_name, s1.middle_name, s1.last_name, s2.subgroup 
                    FROM students as s1 
                    JOIN students_to_subgroups as s2
                    ON s1.id = s2.student_id
                    WHERE s1.id IN (
                        SELECT student_id FROM students_to_groups 
                        WHERE group_id = $group_num
                    ) ORDER BY middle_name)

                SELECT max(subgroup) FROM T1;"
                );

                if (!$sg) {
                    echo '<script type="text/javascript">alert("bad query")</script>';
                    exit;
                }

                $sg_num = pg_fetch_row($sg)[0];

                $target = $_GET["move_to"];
                $id_check = $_GET["student"];

                if ($target == -1) {
                    $target = $sg_num + 1;
                }

                pg_query($connection, 
                    "UPDATE students_to_subgroups
                    SET subgroup = $target
                    WHERE student_id = $id_check
                    ;"
                );

            }
        ?>

        <?php
            function display_group_controls()
            {
                $clink = $_SERVER['PHP_SELF'] . "?" 
                . http_build_query($_GET);

                $divide = $clink . "&split_by_alphabet=true";

                echo "
                    <div class = 'button_label'>
                        Group Controls
                    </div>

                    <div class = 'my_button'>
                        <a href=$divide>Распределить по алфавиту</a>
                    </div>
                ";
            }
        ?>

        <?php
            function split_func()
            {
                $connection = pg_connect("
                    host=localhost 
                    dbname=uni_practic 
                    user=postgres
                    password=postgres
                ");
                
                if (!$connection) {
                    echo '<script type="text/javascript">alert("database connection failed")</script>';
                    exit;
                }

                $group_num = $_GET["group"];

                $result = pg_query($connection, 
                    "WITH T1 AS (
                        SELECT s1.id, s1.first_name, s1.middle_name, s1.last_name, s2.subgroup 
                        FROM students as s1 
                        JOIN students_to_subgroups as s2
                        ON s1.id = s2.student_id
                        WHERE s1.id IN (
                            SELECT student_id FROM students_to_groups 
                            WHERE group_id = $group_num
                        ) ORDER BY middle_name
                    )
                    
                    SELECT id, middle_name
                    FROM T1
                    ORDER BY middle_name;"
                );

                $row_num = pg_num_rows($result);

                $i = 1;

                while($row = pg_fetch_row($result))
                {
                    $s_id = $row[0];

                    if ($i > $row_num / 2)
                    {
                        pg_query($connection, 
                            "UPDATE students_to_subgroups
                            SET subgroup = 2
                            WHERE student_id = $s_id;"
                        );
                    }
                    else 
                    {
                        pg_query($connection, 
                            "UPDATE students_to_subgroups
                            SET subgroup = 1
                            WHERE student_id = $s_id;"
                        );
                    }

                    $i+=1;
                }
            }
        ?>

    </head>

    <body>

        <?php
            if (isset($_GET["move_to"])) {

                move_func();

                unset($_GET["move_to"]);
                $clink = $_SERVER['PHP_SELF'] . "?" 
                . http_build_query($_GET);
            }
        ?>

        <?php
            if (isset($_GET["split_by_alphabet"])) {

                split_func();

                unset($_GET["split_by_alphabet"]);
                $clink = $_SERVER['PHP_SELF'] . "?" 
                . http_build_query($_GET);
            }
        ?>

        <div class="head_foot" id="header">
            
        </div>

        <div class="head_foot" id="student_selector">
            <div class="prompt" id="student_prompt">
                Student Info
            </div>

            <div id="student_info">
                <?php
                    if (isset($_GET['student'])) {
                        display_student($_GET['student']);
                    }
                ?>
            </div>
        </div>

        <div class='head_foot' id='mid'>

            <div id='button_stack_1'>

                <div class = 'button_label'>
                    Group List
                </div>

                <?php
                    display_groups();
                ?>
                
            </div>

            <div id='table_display'>
                <?php

                    // Функции вывода таблиц из бд,
                    // см. ниже для объяснения

                    if (isset($_GET['sc_group'])) {
                        // display_table('sc_group');
                    }
                    elseif (isset($_GET['students'])) {
                        // display_table('students');
                    }
                    elseif (isset($_GET['students_to_groups'])) {
                        // display_table('students_to_groups');
                    }
                    elseif (isset($_GET['students_to_subgroups'])) {
                        // display_table('students_to_subgroups');
                    }
                    elseif (isset($_GET['group_selected'])) {
                        display_group_sg();
                    }
                    else 
                    {
                        echo '
                        <table>   
                            <tr>
                                <th>
                                </th>
                            </tr>
                        </table>
                        ';
                    }
                ?>
            </div>

            <div id='button_stack_2'>

                <?php
                    if (isset($_GET['group']))
                    {
                        display_group_controls();
                    }
                ?>

                <?php
                    // Это вывод самих таблиц из бд,
                    // нужно было в процессе разработки.
                    // Оставил в качестве комментариев,
                    // т.к. может пригодиться позже

                    // if ($_GET['dev_info'] == TRUE)
                    // {
                    //     echo "
                    //         <div class = 'button_label'>
                    //             Dev Info
                    //         </div>

                    //         <div class = 'my_button'>
                    //             <a href='index.php?sc_group=true'>sc_group</a>
                    //         </div>

                    //         <div class = 'my_button'>
                    //             <a href='index.php?students=true'>students</a>
                    //         </div>

                    //         <div class = 'my_button'>
                    //             <a href='index.php?students_to_groups=true'>students_to_groups</a>
                    //         </div>

                    //         <div class = 'my_button'>
                    //             <a href='index.php?students_to_subgroups=true'>subgroups</a>
                    //         </div> 
                    //     ";
                    // }
                ?>
                
            </div>

        </div>

        <div class="head_foot" id="student_controls">
            <div class="prompt" id="student_prompt">
                Student Controls
            </div>

            <div id="controls_buttons">
                <?php
                    display_controls();
                ?>
            </div>
        </div>

        <div class="head_foot" id="footer">

        </div>
    </body>
</html>