document.addEventListener('DOMContentLoaded', () => {
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const aboutUsBtn = document.getElementById('aboutUsBtn');
    const loginDropdown = document.getElementById('loginDropdown');
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const aboutUsModal = document.getElementById('aboutUsModal');
    const closeBtns = document.querySelectorAll('.close-btn');
    const loginForm = document.getElementById('loginForm');
    const registrationForm = document.getElementById('registrationForm');
    const modalRoleTitle = document.getElementById('modalRoleTitle');
    const usernameLabel = document.getElementById('usernameLabel');
    const usernameInput = document.getElementById('username');
    
    let currentRole = '';

    // --- Modal Functions ---
    function openModal(modal) {
        modal.classList.add('show');
    }

    function closeModal(modal) {
        modal.classList.remove('show');
        if (modal === loginModal) {
            loginForm.reset();
        }
        if (modal === registerModal) {
            registrationForm.reset();
        }
    }

    // Close Modals
    closeBtns.forEach(btn => {
        btn.onclick = () => {
            closeModal(btn.closest('.modal'));
        };
    });

    window.onclick = (event) => {
        if (event.target == loginModal) closeModal(loginModal);
        if (event.target == registerModal) closeModal(registerModal);
        if (event.target == aboutUsModal) closeModal(aboutUsModal);
    };

    // --- Button Event Listeners ---
    loginBtn.addEventListener('click', () => {
        loginDropdown.classList.toggle('show');
    });

    registerBtn.addEventListener('click', () => {
        loginDropdown.classList.remove('show');
        openModal(registerModal);
    });

    aboutUsBtn.addEventListener('click', () => {
        loginDropdown.classList.remove('show');
        openModal(aboutUsModal);
    });
    
    // Hide dropdown when clicking elsewhere
    document.addEventListener('click', (event) => {
        if (!event.target.closest('.header-buttons') && !event.target.closest('.login-dropdown')) {
            loginDropdown.classList.remove('show');
        }
    });

    // --- Login Role Selection ---
    loginDropdown.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            currentRole = link.dataset.role;
            loginDropdown.classList.remove('show');
            
            let title = '';
            let label = '';
            let placeholder = '';

            switch (currentRole) {
                case 'adviser':
                    title = 'ICS-ISC Adviser';
                    label = 'Adviser Email';
                    placeholder = 'e.g. admin@ics.ph';
                    break;
                case 'officer':
                    title = 'ICS-ISC Officer';
                    label = 'ID Number or Email';
                    placeholder = 'e.g. officer@ics.ph';
                    break;
                case 'student':
                    title = 'Student';
                    label = 'ID Number';
                    placeholder = 'e.g. 235375';
                    break;
            }

            modalRoleTitle.textContent = title;
            usernameLabel.textContent = label;
            usernameInput.placeholder = placeholder;
            
            openModal(loginModal);
        });
    });

    // --- Login Submission (Connected to PHP) ---
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const username = usernameInput.value.trim();
        const password = document.getElementById('password').value.trim();
        
        if (!username || !password) {
            alert('Please fill in all fields');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            formData.append('role', currentRole);

            const response = await fetch('auth/login.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                window.location.href = result.data.redirect;
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Login Error:', error);
            alert('Login failed. Please try again.');
        }
    });

    // --- Registration Submission (Connected to PHP) ---
    registrationForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const password = document.getElementById('regPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (password !== confirmPassword) {
            alert('Passwords do not match!');
            return;
        }

        if (password.length < 6) {
            alert('Password must be at least 6 characters long');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('id_number', document.getElementById('regId').value);
            formData.append('first_name', document.getElementById('firstName').value);
            formData.append('middle_name', document.getElementById('middleName').value);
            formData.append('last_name', document.getElementById('lastName').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('password', password);
            formData.append('confirm_password', confirmPassword);
            formData.append('year_level', document.getElementById('yearLevel').value);
            formData.append('religion', document.getElementById('religion').value);

            const response = await fetch('auth/register.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                closeModal(registerModal);
                
                // Open login modal for student
                currentRole = 'student';
                modalRoleTitle.textContent = 'Student';
                usernameLabel.textContent = 'ID Number';
                usernameInput.placeholder = 'e.g. 235375';
                openModal(loginModal);
                
                registrationForm.reset();
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Registration Error:', error);
            alert('Registration failed. Please try again.');
        }
    });

    // Cancel button for registration
    const cancelRegBtn = registerModal.querySelector('.btn-cancel-red');
    if (cancelRegBtn) {
        cancelRegBtn.addEventListener('click', () => {
            closeModal(registerModal);
        });
    }
});