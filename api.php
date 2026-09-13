<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

$host = '127.0.0.1';
$db   = 'interview_ai';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (Throwable $e) {
    respond(false, 'Database connection failed. Import database.sql and make sure MySQL is running.');
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? '';

function respond(bool $success, string $message='', array $extra=[]): never {
    echo json_encode(array_merge(['success'=>$success,'message'=>$message],$extra));
    exit;
}
function clean(string $v): string { return trim($v); }

try {
    if ($action === 'register') {
        $name=clean((string)($input['name']??'')); $email=strtolower(clean((string)($input['email']??''))); $password=(string)($input['password']??'');
        if ($name==='' || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($password)<6) respond(false,'Please provide a valid name, email, and password of at least 6 characters.');
        $check=$pdo->prepare("SELECT id FROM users WHERE email=? LIMIT 1"); $check->execute([$email]);
        if ($check->fetch()) respond(false,'That email is already registered.');
        $hash=password_hash($password,PASSWORD_DEFAULT);
        $stmt=$pdo->prepare("INSERT INTO users(name,email,password_hash,role) VALUES(?,?,?,'INTERVIEWER')");
        $stmt->execute([$name,$email,$hash]);
        $id=(int)$pdo->lastInsertId();
        respond(true,'Account created.', ['user'=>['id'=>$id,'name'=>$name,'email'=>$email,'role'=>'INTERVIEWER']]);
    }

    if ($action === 'login') {
        $email=strtolower(clean((string)($input['email']??''))); $password=(string)($input['password']??'');
        $stmt=$pdo->prepare("SELECT id,name,email,password_hash,role FROM users WHERE email=? LIMIT 1"); $stmt->execute([$email]); $u=$stmt->fetch();
        if (!$u || !password_verify($password,$u['password_hash'])) respond(false,'Invalid email or password.');
        respond(true,'Login successful.', ['user'=>['id'=>(int)$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']]]);
    }

    if ($action === 'create_interview') {
        $system=clean((string)($input['system_type']??''));
$role=clean((string)($input['role']??''));
$title=clean((string)($input['title']??''));
$focus=clean((string)($input['focus']??''));

if($system==='' || $role==='') respond(false,'System type and interviewee role are required.');

$code='INT-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(2)),0,4));

$stmt=$pdo->prepare("INSERT INTO interviews(interview_code,system_type,interviewee_role,title,user_role,interview_goal,status) VALUES(?,?,?,?,?,?, 'DRAFT')");
$stmt->execute([$code,$system,$role,$title,$role,$focus]);
        $id=(int)$pdo->lastInsertId();
        $templates=[
    ['Workflow','How do you currently complete your main tasks using the existing system or process?','CRITICAL'],
    ['Workflow','Can you describe the complete process from the beginning to the end?','CRITICAL'],
    ['Workflow','What is the first step you normally take when performing this task?','IMPORTANT'],
    ['Workflow','What steps do you perform most frequently?','IMPORTANT'],
    ['Workflow','Which tasks require you to use multiple systems, applications, or documents?','IMPORTANT'],
    ['Workflow','Are there any steps that are repetitive or take too much time?','IMPORTANT'],
    ['Workflow','Who is responsible for each major step in the process?','IMPORTANT'],
    ['Workflow','What happens when a task cannot be completed normally?','IMPORTANT'],

    ['Pain Points','What part of the current process takes the most time?','CRITICAL'],
    ['Pain Points','What part of the current process causes the most frustration?','CRITICAL'],
    ['Pain Points','What problems do you encounter most often?','CRITICAL'],
    ['Pain Points','Are there tasks that you frequently have to repeat?','IMPORTANT'],
    ['Pain Points','Do you experience delays when completing your work? Why?','IMPORTANT'],
    ['Pain Points','What information is difficult to find or access?','IMPORTANT'],
    ['Pain Points','What manual tasks could be automated?','IMPORTANT'],
    ['Pain Points','What mistakes commonly occur during the current process?','IMPORTANT'],

    ['Information','What information do you need to access most frequently?','IMPORTANT'],
    ['Information','What information do you need to enter into the system?','IMPORTANT'],
    ['Information','Where does the information currently come from?','IMPORTANT'],
    ['Information','Who provides the information you need?','IMPORTANT'],
    ['Information','What information should be available immediately when you log in?','RECOMMENDED'],
    ['Information','How do you currently search for or retrieve information?','IMPORTANT'],
    ['Information','Are there any reports or documents that you regularly need?','RECOMMENDED'],
    ['Information','What information should be restricted to authorized users?','CRITICAL'],

    ['Limitations','What are the biggest limitations of the current system or process?','CRITICAL'],
    ['Limitations','What can the current system not do that you need it to do?','CRITICAL'],
    ['Limitations','Are there system errors or technical problems that occur frequently?','IMPORTANT'],
    ['Limitations','Are there situations where you cannot access the system?','IMPORTANT'],
    ['Limitations','Does the current system become slow or unavailable during certain times?','IMPORTANT'],
    ['Limitations','What information or functions are missing from the current system?','IMPORTANT'],
    ['Limitations','Are there restrictions that make your work more difficult?','IMPORTANT'],
    ['Limitations','What problems occur when many users access the system at the same time?','RECOMMENDED'],

    ['Expectations','What would you want an improved system to make easier?','RECOMMENDED'],
    ['Expectations','What would make the system easier for you to use?','RECOMMENDED'],
    ['Expectations','What would you expect to see on the main dashboard?','RECOMMENDED'],
    ['Expectations','What would make you trust the new system?','RECOMMENDED'],
    ['Expectations','How quickly should the system respond to your actions?','RECOMMENDED'],
    ['Expectations','What type of notifications or alerts would be useful to you?','RECOMMENDED'],

    ['Features','Which features would have the biggest positive impact on your daily work?','RECOMMENDED'],
    ['Features','What features would you consider essential?','CRITICAL'],
    ['Features','What features would be helpful but not essential?','RECOMMENDED'],
    ['Features','Would you need search, filtering, or sorting features? How would you use them?','RECOMMENDED'],
    ['Features','Would you need reports or printable documents? What should they contain?','RECOMMENDED'],
    ['Features','Would you need the system to send automatic notifications or reminders?','RECOMMENDED'],

    ['Security','Who should be allowed to access the system?','CRITICAL'],
    ['Security','What information should only be visible to certain users or roles?','CRITICAL'],
    ['Security','What security or privacy concerns do you have about the proposed system?','CRITICAL'],
    ['Security','What actions should require confirmation or additional authorization?','IMPORTANT'],

    ['Exceptions','Are there unusual situations where the normal process does not work?','IMPORTANT'],
    ['Exceptions','What happens when incorrect or incomplete information is submitted?','IMPORTANT'],
    ['Exceptions','What should the system do when an unexpected problem occurs?','IMPORTANT'],

    ['Success','How would you know that the new system is working better?','RECOMMENDED'],
    ['Success','What improvements would make you consider the new system successful?','RECOMMENDED']
];
        $ins=$pdo->prepare("INSERT INTO interview_questions(interview_id,category,question,priority) VALUES(?,?,?,?)");
        $questions=[];
        foreach($templates as $t){$ins->execute([$id,$t[0],$t[1],$t[2]]);$questions[]=['category'=>$t[0],'question'=>$t[1],'priority'=>$t[2]];}
        respond(true,'Interview created.',['interview'=>['id'=>$id,'system_type'=>$system,'role'=>$role,'title'=>$title?:'Requirement Discovery Interview'],'questions'=>$questions]);
    }

    if ($action === 'save_response') {
    $interview_id = (int)($input['interview_id'] ?? 0);
    $speaker = clean((string)($input['speaker'] ?? 'INTERVIEWEE'));
    $response_text = clean((string)($input['response_text'] ?? ''));

    if ($interview_id <= 0) {
        respond(false, 'Invalid interview ID.');
    }

    if (!in_array($speaker, ['INTERVIEWER', 'INTERVIEWEE'], true)) {
        respond(false, 'Invalid speaker.');
    }

    if ($response_text === '') {
        respond(false, 'Please enter an answer before saving.');
    }

    // Make sure the interview exists.
    $check = $pdo->prepare("SELECT id FROM interviews WHERE id=? LIMIT 1");
    $check->execute([$interview_id]);

    if (!$check->fetch()) {
        respond(false, 'Interview not found.');
    }

    // Save the response.
    $stmt = $pdo->prepare(
        "INSERT INTO interview_responses
        (interview_id, speaker, response_text)
        VALUES (?, ?, ?)"
    );

    $stmt->execute([
        $interview_id,
        $speaker,
        $response_text
    ]);

    // Mark the interview as in progress.
    $update = $pdo->prepare(
        "UPDATE interviews
         SET status='IN_PROGRESS'
         WHERE id=?"
    );

    $update->execute([$interview_id]);

    $response_id = (int)$pdo->lastInsertId();

    respond(true, 'Answer saved.', [
        'response' => [
            'id' => $response_id,
            'interview_id' => $interview_id,
            'speaker' => $speaker,
            'response_text' => $response_text
        ]
    ]);
}

if ($action === 'list_interviews') {
    $rows=$pdo->query("SELECT id,system_type,interviewee_role,title,status,created_at FROM interviews ORDER BY id DESC")->fetchAll();
    respond(true,'',['interviews'=>$rows]);
}

    respond(false,'Unknown action.');
} catch (Throwable $e) {
    respond(false,'Server error: '.$e->getMessage());
}
?>