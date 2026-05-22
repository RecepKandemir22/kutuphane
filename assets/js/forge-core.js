/**
 * ForgeCore - Client-side interactive bindings and helper library for CodeForge-Engine.
 */
const Forge = {
    /**
     * Display a sleek, micro-animated notification toast
     */
    toast: function(message, type = 'info', duration = 3000) {
        let container = document.querySelector('.forge-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'forge-toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `forge-toast forge-toast-${type}`;
        toast.innerHTML = `
            <span>${message}</span>
            <button class="forge-toast-close">&times;</button>
        `;

        container.appendChild(toast);

        // Slide-in animation trigger
        setTimeout(() => {
            toast.classList.add('show');
        }, 10);

        // Close on button click
        toast.querySelector('.forge-toast-close').addEventListener('click', () => {
            this.closeToast(toast);
        });

        // Auto close
        const timeoutId = setTimeout(() => {
            this.closeToast(toast);
        }, duration);

        toast.dataset.timeoutId = timeoutId;
    },

    closeToast: function(toast) {
        if (toast.dataset.timeoutId) {
            clearTimeout(toast.dataset.timeoutId);
        }
        toast.classList.remove('show');
        toast.addEventListener('transitionend', () => {
            toast.remove();
        });
    },

    /**
     * Show or Hide a glassmorphic Modal dialog
     */
    modal: function(modalId, action = 'show') {
        const backdrop = document.getElementById(modalId);
        if (!backdrop) return;

        if (action === 'show') {
            backdrop.classList.add('show');
            document.body.style.overflow = 'hidden'; // Lock background scroll
        } else {
            backdrop.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Auto-bind close elements if they haven't been bound
        if (!backdrop.dataset.bound) {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    this.modal(modalId, 'hide');
                }
            });

            const closeBtn = backdrop.querySelector('.forge-modal-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.modal(modalId, 'hide');
                });
            }
            backdrop.dataset.bound = 'true';
        }
    },

    /**
     * Submit form via AJAX using JSON
     */
    ajaxForm: function(formId, successCallback, errorCallback) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            
            // Basic Client-side Validation check first
            if (this.validateForm(form)) {
                const formData = new FormData(form);
                const action = form.getAttribute('action') || window.location.href;
                const method = form.getAttribute('method') || 'POST';

                // Check for CSRF token in page meta or form
                const headers = {
                    'X-Requested-With': 'XMLHttpRequest'
                };
                
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
                }

                fetch(action, {
                    method: method.toUpperCase(),
                    headers: headers,
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw response;
                    }
                    return response.json();
                })
                .then(data => {
                    if (successCallback) {
                        successCallback(data);
                    } else {
                        if (data.message) this.toast(data.message, 'success');
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        }
                    }
                })
                .catch(err => {
                    if (err instanceof Response) {
                        err.json().then(errorData => {
                            if (errorCallback) {
                                errorCallback(errorData);
                            } else {
                                const msg = errorData.error || errorData.message || 'An error occurred during submission.';
                                this.toast(msg, 'danger');
                            }
                        }).catch(() => {
                            if (errorCallback) errorCallback(err);
                            else this.toast('An error occurred. Status code: ' + err.status, 'danger');
                        });
                    } else {
                        if (errorCallback) errorCallback(err);
                        else this.toast('Network error or connection lost.', 'danger');
                    }
                });
            }
        });
    },

    /**
     * Simple Client-side Form Validation
     */
    validateForm: function(form) {
        let isValid = true;
        const requiredInputs = form.querySelectorAll('[required]');

        // Clear existing errors
        form.querySelectorAll('.forge-error-message').forEach(el => el.remove());
        form.querySelectorAll('.forge-input').forEach(el => el.style.borderColor = '');

        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.borderColor = 'var(--danger)';
                
                // Add error text
                const error = document.createElement('span');
                error.className = 'forge-error-message';
                error.style.color = 'var(--danger)';
                error.style.fontSize = '12px';
                error.style.marginTop = '4px';
                error.innerText = 'This field is required.';
                
                input.parentNode.appendChild(error);
            } else if (input.type === 'email' && !this.isValidEmail(input.value)) {
                isValid = false;
                input.style.borderColor = 'var(--danger)';
                
                const error = document.createElement('span');
                error.className = 'forge-error-message';
                error.style.color = 'var(--danger)';
                error.style.fontSize = '12px';
                error.style.marginTop = '4px';
                error.innerText = 'Invalid email address.';
                
                input.parentNode.appendChild(error);
            }
        });

        return isValid;
    },

    isValidEmail: function(email) {
        const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    },

    /**
     * Switch Tabs or View Panels dynamically
     */
    initTabs: function(tabContainerSelector) {
        const container = document.querySelector(tabContainerSelector);
        if (!container) return;

        const triggers = container.querySelectorAll('[data-tab-target]');
        triggers.forEach(trigger => {
            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = trigger.getAttribute('data-tab-target');
                const targetPanel = document.querySelector(targetId);

                if (!targetPanel) return;

                // Deactivate other tabs
                triggers.forEach(t => t.classList.remove('active'));
                const panels = targetPanel.parentNode.querySelectorAll('.forge-tab-panel');
                panels.forEach(p => p.style.display = 'none');

                // Activate this one
                trigger.classList.add('active');
                targetPanel.style.display = 'block';
            });
        });
    }
};

// Initialize default features
document.addEventListener('DOMContentLoaded', () => {
    // Auto bind close buttons on alerts
    document.querySelectorAll('.forge-alert-close').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.closest('.forge-alert').remove();
        });
    });
});
