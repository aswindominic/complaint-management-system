# complaint-management-system
The Complaint Management System is a full-stack web application developed to streamline the process of registering, managing, and resolving complaints for various electronic devices. The system supports multiple user roles including users, administrators, and technicians, enabling efficient communication and resolution workflows.

  FEATURES
 1. User module
  •	User registration and secure login
	•	Submit complaints for multiple device types (Smartphone, Laptop, Smartwatch, etc.)
	•	Upload images related to complaints
	•	View complaint status (Pending, In Progress, Resolved)
	•	Real-time technician availability display
	•	Chat system to communicate with assigned technician
	•	Password reset via email (using PHPMailer)
  . Can submit review and view admin responses .
2. Admin module
  •	Admin dashboard to monitor all complaints
	•	Filter complaints by device type, status, and date
  . Add technicians,Send invitation gmail including password and username to technicians . 
	•	Assign complaints to technicians
	•	Update complaint status
	•	Manage technician availability
	•	Manage users (view, search, delete)
  . View user reviews and respond to user reviews.
3. Technician module
  •	View assigned complaints
	•	Update complaint status to resolved
	•	Communicate with users via chat system
	•	Work based on device-specific grouping.

TECHNOLOGIES USED
	•	Frontend: HTML, CSS, JavaScript
	•	Backend: PHP
	•	Database: MySQL
	•	Email Integration: PHPMailer
	•	Server Environment: XAMPP
INSTALLATION AND SETUP
Prerequestries 
  Install XAMPP
Steps to Run 
1. Download or clone this repository.
2. move the project folder to htdocs in xampp.
3. Start APACHE and MySQL using XAMPP control panel.
4. Open PhpMyAdmin.
5. create a new database named complaint_system
6. import the file : complaint_system.sql
7. open your browser and navigate to : http://localhost/complaint-management-system

EMAIL FUNCTIONALITY
implemented using PHPMailer
Used for password reset functionality and assing new technicians. 
Requires SMTP configuration for full functionality. 

NOTES
This project is developed and tested on local server(XAMPP).
The uploads/folder may sample or no images.
Ensure proper database configuration before running the project.

AUTHOR
Aswin Dominic
BSc Computer Science Graduate 
Aspiring Software Developer
   
