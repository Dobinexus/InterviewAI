INTERVIEWAI — PROTOTYPE
========================

1. Copy the entire InterviewAI folder into:
   C:\xampp\htdocs\InterviewAI\

2. Start Apache and MySQL in XAMPP.

3. Open phpMyAdmin:
   http://localhost/phpmyadmin/

4. Import database.sql.

5. Open:
   http://localhost/InterviewAI/

6. Create Account now writes the new account to MySQL through api.php.
   Passwords are stored using PHP password_hash(), not plain text.

Demo:
Email: demo@interviewai.local
Password: password

If your MySQL root account has a password, edit the $pass variable in api.php.
