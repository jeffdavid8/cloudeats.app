/**
 * Ancestry Admin Users Component
 * Handles user management interface with add/edit/delete functionality and CSRF protection
 */

mb.registerComponent('ancestry-admin-users', function($element, data) {
    
    async function loadUsers() {
        const r = await fetch('/apps/ancestry/auth/users_api.php', {credentials:'same-origin'});
        const j = await r.json();
        const out = document.getElementById('users');
        if (!j) { 
            out.textContent='No users'; 
            return; 
        }
        const rows = ['<table><tr><th>user</th><th>created</th><th>canadmin</th><th>actions</th></tr>'];
        Object.keys(j).forEach(u => { 
            const it = j[u]; 
            rows.push('<tr><td>'+u+'</td><td>'+ (it.created||'') +'</td><td>'+(it.canadmin||'')+'</td><td><button data-user="'+u+'" class="edit">Edit</button> <button data-user="'+u+'" class="del">Delete</button></td></tr>'); 
        });
        rows.push('</table>'); 
        out.innerHTML = rows.join(''); 
        
        // Add delete event listeners
        Array.from(document.getElementsByClassName('del')).forEach(b => 
            b.addEventListener('click', async (e) => { 
                if(!confirm('Delete '+e.target.dataset.user+'?')) return; 
                const token = window.CSRF_TOKEN; 
                await fetch('/apps/ancestry/auth/users_api.php', {
                    method:'DELETE', 
                    headers:{'Content-Type':'application/json'}, 
                    body: JSON.stringify({_csrf: token, user: e.target.dataset.user}), 
                    credentials:'same-origin'
                }); 
                loadUsers(); 
            })
        );
        
        // Add edit event listeners
        Array.from(document.getElementsByClassName('edit')).forEach(b => 
            b.addEventListener('click', (e) => { 
                showEdit(e.target.dataset.user, j[e.target.dataset.user]); 
            })
        );
    }

    function showEdit(username, data) {
        document.getElementById('edit_user').value = username;
        document.getElementById('editUserName').textContent = username;
        document.getElementById('edit_canadmin').checked = (data.canadmin && data.canadmin !== 'N');
        document.getElementById('editPanel').style.display = 'block';
    }

    // Add user form handler
    document.getElementById('addUserForm').addEventListener('submit', async function(ev) { 
        ev.preventDefault(); 
        const f = new FormData(this); 
        const body = { 
            new_user: f.get('new_user'), 
            new_pass: f.get('new_pass'), 
            canadmin: f.get('canadmin') ? 'Y' : 'N', 
            _csrf: window.CSRF_TOKEN 
        }; 
        const r = await fetch('/apps/ancestry/auth/users_api.php', {
            method:'POST', 
            headers:{'Content-Type':'application/json'}, 
            body: JSON.stringify(body), 
            credentials:'same-origin'
        }); 
        const j = await r.text(); 
        console.log(j); 
        this.reset(); // Clear form
        loadUsers(); 
    });

    // Cancel edit handler
    document.getElementById('cancelEdit').addEventListener('click', () => { 
        document.getElementById('editPanel').style.display='none'; 
    });

    // Edit user form handler
    document.getElementById('editUserForm').addEventListener('submit', async function(ev) { 
        ev.preventDefault(); 
        const f = new FormData(this); 
        const body = { 
            user: f.get('user'), 
            new_pass: f.get('new_pass') || null, 
            canadmin: document.getElementById('edit_canadmin').checked ? 'Y' : 'N', 
            _csrf: window.CSRF_TOKEN 
        }; 
        const r = await fetch('/apps/ancestry/auth/users_api.php', {
            method:'PUT', 
            headers:{'Content-Type':'application/json'}, 
            body: JSON.stringify(body), 
            credentials:'same-origin'
        }); 
        const j = await r.json(); 
        console.log(j); 
        document.getElementById('editPanel').style.display='none'; 
        loadUsers(); 
    });

    // Load external auth.js script
    if (!document.querySelector('script[src="/apps/ancestry/js/auth.js"]')) {
        const script = document.createElement('script');
        script.src = '/apps/ancestry/js/auth.js';
        script.async = true;
        document.head.appendChild(script);
    }

    // Initialize users list
    loadUsers();

}, []);