// Ana JavaScript dosyası
document.addEventListener('DOMContentLoaded', function() {
    
    // Form validasyonu
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#dc3545';
                    isValid = false;
                } else {
                    field.style.borderColor = '#ced4da';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Lütfen tüm gerekli alanları doldurun.');
            }
        });
    });
    
    // Şifre doğrulama
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('password_confirm');
    
    if (passwordField && confirmPasswordField) {
        confirmPasswordField.addEventListener('blur', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.style.borderColor = '#dc3545';
                confirmPasswordField.setCustomValidity('Şifreler eşleşmiyor');
            } else {
                confirmPasswordField.style.borderColor = '#ced4da';
                confirmPasswordField.setCustomValidity('');
            }
        });
    }
    
    // Dosya yükleme drag & drop
    const uploadArea = document.querySelector('.upload-area');
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            const fileInput = document.getElementById('file');
            if (fileInput && files.length > 0) {
                fileInput.files = files;
                updateFileInfo(files[0]);
            }
        });
    }
    
    // Dosya input değişikliği
    const fileInput = document.getElementById('file');
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                updateFileInfo(this.files[0]);
            }
        });
    }
    
    // Dosya bilgilerini güncelle
    function updateFileInfo(file) {
        const fileInfo = document.getElementById('file-info');
        if (fileInfo) {
            fileInfo.innerHTML = `
                <p><strong>Dosya:</strong> ${file.name}</p>
                <p><strong>Boyut:</strong> ${formatFileSize(file.size)}</p>
                <p><strong>Tür:</strong> ${file.type || 'Bilinmiyor'}</p>
            `;
        }
    }
    
    // Dosya boyutunu formatla
    function formatFileSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = bytes;
        let unitIndex = 0;
        
        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }
        
        return size.toFixed(2) + ' ' + units[unitIndex];
    }
    
    // Alert otomatik gizleme
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Grup üye arama
    const memberSearch = document.getElementById('member-search');
    if (memberSearch) {
        memberSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length >= 2) {
                searchUsers(searchTerm);
            } else {
                clearSearchResults();
            }
        });
    }
    
    // Kullanıcı arama fonksiyonu
    function searchUsers(searchTerm) {
        fetch(`../api/search_users.php?q=${encodeURIComponent(searchTerm)}`)
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data);
            })
            .catch(error => {
                console.error('Arama hatası:', error);
            });
    }
    
    // Arama sonuçlarını göster
    function displaySearchResults(users) {
        const resultsDiv = document.getElementById('search-results');
        if (!resultsDiv) return;
        
        if (users.length === 0) {
            resultsDiv.innerHTML = '<p>Kullanıcı bulunamadı.</p>';
            return;
        }
        
        let html = '<div class="search-results-list">';
        users.forEach(user => {
            html += `
                <div class="search-result-item" data-user-id="${user.id}">
                    <span>${user.full_name} (@${user.username})</span>
                    <button type="button" class="btn btn-sm add-member-btn">Ekle</button>
                </div>
            `;
        });
        html += '</div>';
        
        resultsDiv.innerHTML = html;
        
        // Ekle butonlarına olay dinleyici ekle
        const addButtons = resultsDiv.querySelectorAll('.add-member-btn');
        addButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                const userItem = this.parentElement;
                const userId = userItem.dataset.userId;
                const userName = userItem.querySelector('span').textContent;
                addMemberToGroup(userId, userName);
            });
        });
    }
    
    // Arama sonuçlarını temizle
    function clearSearchResults() {
        const resultsDiv = document.getElementById('search-results');
        if (resultsDiv) {
            resultsDiv.innerHTML = '';
        }
    }
    
    // Gruba üye ekle
    function addMemberToGroup(userId, userName) {
        // Bu fonksiyon grup sayfasında implement edilecek
        console.log('Üye ekleniyor:', userId, userName);
    }
});