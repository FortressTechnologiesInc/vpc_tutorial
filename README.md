
---

# **MySQL Installation and Configuration on Amazon Linux 2023 with Webserver Setup**

This guide outlines the steps to create two EC2 instances: one for the **Database Server** (MySQL) and another for the **Web Server**. We will also configure MySQL on the database server and Apache with PHP on the web server to connect and display data from the database.

## **References**
[How to Install MySQL on Amazon Linux 2023](https://muleif.medium.com/how-to-install-mysql-on-amazon-linux-2023-5d39afa5bf11)
[How to Install Apache & PHP on Amazon Linux 2023](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/CHAP_Tutorials.WebServerDB.CreateWebServer.html)

---

## **Steps**

### **1. Create EC2 Instances**
#### **1.1 Database Server EC2 Setup**
1. Login to the AWS Management Console.
2. Navigate to **EC2** and click on **Launch Instance**.
3. **Instance Configuration**:
   - **Name:** Database Server
   - **AMI:** Select **Amazon Linux 2023**.
   - **Instance Type:** Select an instance type like `t2.micro` (free tier eligible).
   - **Key Pair:** Select or create a key pair for SSH access.
   - **Network Settings:**
     - Select the default VPC or a custom one.
     - Open **port 3306** for MySQL access.
     - Set the **private IP** for communication between the web server and the database server.

4. Click **Launch Instance** to start the database server instance.

---

#### **1.2 Web Server EC2 Setup**
1. Repeat the steps to launch another EC2 instance for the **Web Server**.
   - **Instance Configuration:**
     - **Name:** Web Server
     - **AMI:** Select **Amazon Linux 2023**.
     - **Instance Type:** Choose a free-tier eligible type like `t2.micro`.
     - **Key Pair:** Select or create a key pair for SSH access.
     - **Network Settings:**
       - Select the default VPC or custom VPC.
       - Open **port 80** (HTTP) and **3306** (MySQL) in the inbound rules.
       - Ensure that the **web server** can communicate with the **database server** via the private IP.

2. Click **Launch Instance** to start the web server instance.

---

### **2. Open Security Group for Database and Web Servers**

#### **2.1 Database Server**
1. Navigate to **Security Groups** in the EC2 Dashboard.
2. Locate the security group for your **Database Server**.
3. Add an inbound rule:
   - **Type:** MySQL/Aurora  
   - **Protocol:** TCP  
   - **Port Range:** 3306  
   - **Source:** Private IP of your **Web Server**.

#### **2.2 Web Server**
1. Similarly, locate the security group for your **Web Server**.
2. Ensure the following inbound rules:
   - **Type:** HTTP  
   - **Protocol:** TCP  
   - **Port Range:** 80  
   - **Source:** 0.0.0.0/0 (or restrict it to your network as needed).
   - **Type:** MySQL/Aurora  
   - **Protocol:** TCP  
   - **Port Range:** 3306  
   - **Source:** Private IP of your **Database Server**.

---

## **MySQL Installation and Configuration on Database Server**

### **1. Switch to Root User**
```bash
sudo su -
```

### **2. Install MySQL Repository**
```bash
sudo wget https://dev.mysql.com/get/mysql80-community-release-el9-1.noarch.rpm
sudo dnf install mysql80-community-release-el9-1.noarch.rpm -y
```

### **3. Import GPG Key**
```bash
sudo rpm --import https://repo.mysql.com/RPM-GPG-KEY-mysql-2023
```

### **4. Install MySQL Server**
```bash
sudo dnf install mysql-community-server -y
```

### **5. Start and Enable MySQL Service**
```bash
sudo systemctl start mysqld
sudo systemctl enable mysqld
```

### **6. Retrieve Temporary Root Password**
```bash
sudo grep 'temporary password' /var/log/mysqld.log | awk '{print $NF}'
```

### **7. Secure MySQL Installation**
Run the following command and follow the prompts:
```bash
mysql_secure_installation
```
- **Current Password:** Use the temporary password from step 6.  
- **New Password:** Set a secure password like `Admin@12345`. Short passwords will be rejected.  
- **Other Prompts:** Hit `Enter` for defaults unless specific changes are required.

---

## **Database Configuration on Database Server**

### **1. Login to MySQL**
```bash
mysql -u root -p
```

### **2. Create a User**
```sql
CREATE USER 'tutorial_user'@'%' IDENTIFIED BY 'Admin@12345';
GRANT ALL PRIVILEGES ON *.* TO 'tutorial_user'@'%';
FLUSH PRIVILEGES;
SELECT User, Host FROM mysql.user WHERE User = 'tutorial_user';
```

### **3. Create and Select a Database**
```sql
CREATE DATABASE sample;
USE sample;
```

### **4. Create a Table**
```sql
CREATE TABLE EMPLOYEES (
    ID INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(45),
    ADDRESS VARCHAR(90)
);
```

### **5. Verify Table Creation**
```sql
SHOW TABLES;
```

### **6. Insert and View Data**
To see the contents of the `EMPLOYEES` table:
```sql
SELECT * FROM EMPLOYEES;
```

---

## **Web Server Setup**

### **1. Update the Web Server**
```bash
sudo dnf update -y
```

### **2. Install Apache, PHP, and MySQLi**
```bash
sudo dnf install -y httpd php php-mysqli mariadb105 -y
```

### **3. Start and Enable Apache**
```bash
sudo systemctl start httpd
sudo systemctl enable httpd
```

### **4. Add User Permissions for Apache**
```bash
sudo usermod -a -G apache ec2-user
sudo chown -R ec2-user:apache /var/www
sudo chmod 2775 /var/www
```

### **5. Set Directory Permissions for /var/www**
```bash
# Change directory permissions for /var/www and its subdirectories
find /var/www -type d -exec sudo chmod 2775 {} \;

# Recursively change file permissions for /var/www
find /var/www -type f -exec sudo chmod 0664 {} \;
```
Now, `ec2-user` (and any future members of the Apache group) can add, delete, and edit files in the Apache document root.

---

## **Configure Web Server to Connect to MySQL Database**

### **1. Create Database Connection File**
```bash
sudo vi /var/www/inc/dbinfo.inc
```
Add the following content (replace `<DB-SERVER-PRIVATE-IP>` with your **Database Server's private IP**):
```php
<?php
define('DB_SERVER', '<DB-SERVER-PRIVATE-IP>');
define('DB_USERNAME', 'tutorial_user');
define('DB_PASSWORD', 'Admin@12345');
define('DB_DATABASE', 'sample');
?>
```

### **2. Create SamplePage.php File**
```bash
sudo vi /var/www/html/SamplePage.php
```
Add the following content to verify the connection and display data from the database:
```php
<?php include "../inc/dbinfo.inc"; ?>
<html>
<body>
<h1>Sample page</h1>
<?php
  $connection = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

  if (mysqli_connect_errno()) echo "Failed to connect to MySQL: " . mysqli_connect_error();

  $database = mysqli_select_db($connection, DB_DATABASE);
  VerifyEmployeesTable($connection, DB_DATABASE);

  $employee_name = htmlentities($_POST['NAME']);
  $employee_address = htmlentities($_POST['ADDRESS']);

  if (strlen($employee_name) || strlen($employee_address)) {
    AddEmployee($connection, $employee_name, $employee_address);
  }
?>

<form action="<?PHP echo $_SERVER['SCRIPT_NAME'] ?>" method="POST">
  <table border="0">
    <tr>
      <td>NAME</td>
      <td>ADDRESS</td>
    </tr>
    <tr>
      <td><input type="text" name="NAME" maxlength="45" size="30" /></td>
      <td><input type="text" name="ADDRESS" maxlength="90" size="60" /></td>
      <td><input type="submit" value="Add Data" /></td>
    </tr>
  </table>
</form>

<table border="1" cellpadding="2" cellspacing="2">
  <tr>
    <td>ID</td>
    <td>NAME</td>
    <td>ADDRESS</td>
  </tr>

<?php
  $result = mysqli_query($connection, "SELECT * FROM EMPLOYEES");

  while($query_data = mysqli_fetch_row($result)) {
    echo "<tr>";
    echo "<td>",$query_data[0], "</td>",
         "<td>",$query_data[1], "</td>",
         "<td>",$query_data[2], "</td>";
    echo "</tr>";
}
?>

</

table>
</body>
</html>
```

### **3. Verify Database Connection**
Now, you can access the web page:
```bash
http://<WEBSERVER-PUBLIC-IP>/SamplePage.php
```
You should be able to enter data and see it stored in your MySQL database.

---

With this, your **web server** will successfully connect to the **database server**, and you will be able to verify data submission from the **web server**.
