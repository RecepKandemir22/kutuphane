/**
 * ForgeForm & Shield - Client AJAX Engine
 * Zero-Dependency form interceptor, dynamic captcha injector, validation, and notification system.
 */

document.addEventListener('DOMContentLoaded', () => {
    initForgeForms();
});

/**
 * Initializes all forms with class 'forge-form'
 */
function initForgeForms() {
    const forms = document.querySelectorAll('form.forge-form');
    forms.forEach(form => {
        setupFormShield(form);
    });
}

/**
 * Sets up Shield configuration, csrf token, mathematical captcha, and AJAX interceptor for a form.
 */
async function setupFormShield(form) {
    const shieldPath = form.getAttribute('data-shield-path') || 'forge-shield/ForgeShield.php';
    
    // 1. Inject or locate security container
    let shieldWrapper = form.querySelector('.forge-shield-wrapper');
    if (!shieldWrapper) {
        shieldWrapper = document.createElement('div');
        shieldWrapper.className = 'forge-shield-wrapper';
        
        // Find submit button to place the security validation right above it
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.parentNode.insertBefore(shieldWrapper, submitBtn);
        } else {
            form.appendChild(shieldWrapper);
        }
    }
    
    // 2. Fetch security parameters from backend
    try {
        const response = await fetch(`${shieldPath}?forge_action=setup`);
        if (!response.ok) throw new Error('Shield setup failed');
        const data = await response.json();
        
        // Populate inputs
        shieldWrapper.innerHTML = `
            <input type="hidden" name="forge_csrf_token" value="${data.csrf_token}">
            <div class="forge-captcha-group animate-fade-in">
                <label class="forge-captcha-label">
                    <span>Güvenlik Sorusu:</span> 
                    <strong class="forge-captcha-question">${data.captcha}</strong>
                </label>
                <input type="number" name="forge_captcha_answer" class="forge-captcha-input" placeholder="Cevabı girin" required autocomplete="off">
            </div>
        `;
    } catch (error) {
        console.error('ForgeShield Setup Error:', error);
        showToast('Güvenlik sistemi yüklenemedi. Sayfayı yenileyin.', 'danger');
    }
    
    // 3. Intercept submission
    // Prevent duplicate event listener bindings
    if (form.dataset.shieldBound) return;
    form.dataset.shieldBound = 'true';
    
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        // Front-end validity check
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
        const originalBtnText = submitBtn ? (submitBtn.tagName === 'INPUT' ? submitBtn.value : submitBtn.innerHTML) : 'Gönder';
        
        // Disable submission button (Anti double-click protection)
        if (submitBtn) {
            submitBtn.disabled = true;
            if (submitBtn.tagName === 'INPUT') {
                submitBtn.value = 'Gönderiliyor... ⏳';
            } else {
                submitBtn.innerHTML = '<span class="forge-spinner"></span> Gönderiliyor...';
            }
        }
        
        // Compile form fields
        const formData = new FormData(form);
        const actionUrl = form.getAttribute('action') || window.location.href;
        
        try {
            const response = await fetch(actionUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            
            if (result.status === 'success') {
                showToast(result.message || 'Form başarıyla gönderildi.', 'success');
                form.reset();
                // Reload captcha and csrf fields
                setupFormShield(form);
            } else {
                showToast(result.message || 'Bir hata oluştu.', 'danger');
                
                // Re-enable button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtn.tagName === 'INPUT') {
                        submitBtn.value = originalBtnText;
                    } else {
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            }
        } catch (error) {
            console.error('Form AJAX Submit Error:', error);
            showToast('Sunucu bağlantı hatası oluştu.', 'danger');
            
            // Re-enable button
            if (submitBtn) {
                submitBtn.disabled = false;
                if (submitBtn.tagName === 'INPUT') {
                    submitBtn.value = originalBtnText;
                } else {
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        }
    });
}

/**
 * Renders a gorgeous micro-animated notification toast.
 */
function showToast(message, type = 'success') {
    let container = document.querySelector('.forge-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'forge-toast-container';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `forge-toast forge-toast-${type}`;
    
    // Choose appropriate SVG icon
    let iconSvg = '';
    if (type === 'success') {
        iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="forge-toast-icon"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`;
    } else {
        iconSvg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="forge-toast-icon"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
    }
    
    toast.innerHTML = `${iconSvg} <span class="forge-toast-message">${message}</span>`;
    container.appendChild(toast);
    
    // Trigger entrance animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    // Auto-remove toast
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.remove();
        }, 400);
    }, 4500);
}
