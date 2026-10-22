function showMessage(message) {
    alert(message);
}
 const patterns = {
            fullName: /^[a-zA-Z\s]{3,50}$/,  // Letters and spaces, 3-50 chars
            studentId: /^[a-zA-Z0-9]{5,10}$/,  // Alphanumeric, 5-10 chars
            department: /^[a-zA-Z\s]{2,50}$/,  // Letters and spaces, 2-50 chars
            course: /^[a-zA-Z0-9.\s]{2,50}$/,  // Letters, numbers, dots, 2-50 chars
            semester: /^[1-8]$/,  // Numbers 1-8
            email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,  // Basic email validation
            phone: /^[0-9]{10}$/  // Exactly 10 digits
        };
        
        function validateProfileForm() {
            const form = document.getElementById('profileForm');
            const fullName = document.getElementById('full_name').value.trim();
            const studentId = document.getElementById('student_id').value.trim();
            const department = document.getElementById('department').value.trim();
            const course = document.getElementById('course').value.trim();
            const semester = document.getElementById('semester').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            
            let isValid = true;
            
            // Validate Full Name
            if (!patterns.fullName.test(fullName)) {
                document.getElementById('full_name_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('full_name_error').style.display = 'none';
            }
            
            // Validate Student ID
            if (!patterns.studentId.test(studentId)) {
                document.getElementById('student_id_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('student_id_error').style.display = 'none';
            }
            
            // Validate Department
            if (!patterns.department.test(department)) {
                document.getElementById('department_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('department_error').style.display = 'none';
            }
            
            // Validate Course
            if (!patterns.course.test(course)) {
                document.getElementById('course_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('course_error').style.display = 'none';
            }
            
            // Validate Semester
            if (!patterns.semester.test(semester)) {
                document.getElementById('semester_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('semester_error').style.display = 'none';
            }
            
            // Validate Email
            if (!patterns.email.test(email)) {
                document.getElementById('email_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('email_error').style.display = 'none';
            }
            
            // Validate Phone
            if (!patterns.phone.test(phone)) {
                document.getElementById('phone_error').style.display = 'inline';
                isValid = false;
            } else {
                document.getElementById('phone_error').style.display = 'none';
            }
            
            // If all validations pass, show success message
            if (isValid) {
                showMessage('Your Profile is Submitted!');
            }
        }
