

---

# **MySQL Installation and Configuration on Amazon Linux 2023**

This guide outlines the steps to create an EC2 instance, install MySQL on Amazon Linux 2023, set up a database, and create a user and table.

## **Reference**
[How to Install MySQL on Amazon Linux 2023](https://muleif.medium.com/how-to-install-mysql-on-amazon-linux-2023-5d39afa5bf11)

---

## **Steps**

### **1. Create an EC2 Instance**
1. Login to the AWS Management Console.
2. Navigate to **EC2** and click on **Launch Instance**.
3. **Instance Configuration**:
   - **Name:** Database Server  
   - **AMI:** Select **Amazon Linux 2023**.
   - **Instance Type:** Select an instance type like `t2.micro` (free tier eligible).  
   - **Key Pair:** Select or create a key pair for SSH access.  
   - **Network Settings:**
     - Select the default VPC or a custom one.
     - Open **port 3306** to allow MySQL traffic.
     - Use the private IP address of the web server for enhanced security.
4. Click **Launch Instance** to start your database server.

---

### **2. Open Security Group for Port 3306**
1. Navigate to **Security Groups** in the EC2 Dashboard.
2. Find and edit the security group associated with your EC2 instance.
3. Add an inbound rule:
   - **Type:** MySQL/Aurora  
   - **Protocol:** TCP  
   - **Port Range:** 3306  
   - **Source:** Private IP of your web server.

---

## **MySQL Installation**

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

## **Database Configuration**

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

## **Notes**
- Replace `Admin@12345` with a secure password of your choice.
- Use private IPs and security groups for enhanced security.
- Follow the reference link for additional troubleshooting or updates.

---
