/**
 * Avatar cropper (Cropper.js) — configuración leída desde <textarea data-avatar-cropper-config>.
 */
export function initAvatarCroppers() {
    document.querySelectorAll('textarea[data-avatar-cropper-config]').forEach((el) => {
        const raw = el.value || el.textContent || '';
        if (!raw.trim()) {
            return;
        }
        let config;
        try {
            config = JSON.parse(raw);
        } catch {
            return;
        }
        initAvatarCropperInstance(config);
    });
}

/**
 * @param {Record<string, unknown>} config
 */
function initAvatarCropperInstance(config) {
    const {
        mode,
        previewId,
        base64InputId,
        openButtonId,
        cropImageId,
        modalId,
        cropSource,
        cropSourceInputId,
        dataTransferInputId,
        formId,
        stepOneId,
        stepTwoId,
    } = config;

    const openBtn = document.getElementById(String(openButtonId));
    const base64Input = document.getElementById(String(base64InputId));
    const preview = document.getElementById(String(previewId));
    const imageToEdit = document.getElementById(String(cropImageId));
    const modal = document.getElementById(String(modalId));
    const cancelBtn = document.getElementById(`${modalId}_btnCancel`);
    const resetBtn = document.getElementById(`${modalId}_btnReset`);
    const applyBtn = document.getElementById(`${modalId}_btnApply`);

    const cropSourceInput = cropSourceInputId ? document.getElementById(String(cropSourceInputId)) : null;
    const dataTransferInput = dataTransferInputId ? document.getElementById(String(dataTransferInputId)) : null;
    const form = formId ? document.getElementById(String(formId)) : null;
    const step1 = stepOneId ? document.getElementById(String(stepOneId)) : null;
    const step2 = stepTwoId ? document.getElementById(String(stepTwoId)) : null;

    const step1Fields =
        mode === 'register' && step1
            ? ['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'country'].map((id) =>
                  document.getElementById(id),
              )
            : [];

    const goStep2 = mode === 'register' ? document.getElementById('go-step-2') : null;
    const backStep1 = mode === 'register' ? document.getElementById('back-step-1') : null;
    const firstNameField = mode === 'register' ? document.getElementById('first_name') : null;
    const lastNameField = mode === 'register' ? document.getElementById('last_name') : null;
    const descriptionField = mode === 'register' ? document.getElementById('description') : null;
    const emailField = mode === 'register' ? document.getElementById('email') : null;
    const passwordField = mode === 'register' ? document.getElementById('password') : null;
    const passwordConfirmationField = mode === 'register' ? document.getElementById('password_confirmation') : null;
    const countryField = mode === 'register' ? document.getElementById('country') : null;
    const allowedCountries = new Set(['Colombia', 'México', 'Argentina', 'Chile', 'Perú', 'Ecuador', 'Venezuela', 'Bolivia', 'Paraguay', 'Uruguay']);

    if (!openBtn || !base64Input || !preview || !imageToEdit || !modal || !cancelBtn || !resetBtn || !applyBtn) {
        return;
    }

    if (mode === 'profile' && cropSource === 'persistent' && !cropSourceInput) {
        return;
    }

    if (mode === 'register' && cropSource === 'dynamic' && !dataTransferInput) {
        return;
    }

    let cropper = null;
    let objectUrl = null;

    const resolveCropperConstructor = () => window.Cropper?.default || window.Cropper;

    const setFieldError = (field, message = '') => {
        if (!field) {
            return;
        }

        field.setCustomValidity(message);

        const errorEl = document.getElementById(`${field.id}_client_error`);
        const hasError = message !== '';

        field.classList.toggle('border-rose-400', hasError);
        field.classList.toggle('ring-2', hasError);
        field.classList.toggle('ring-rose-400/50', hasError);

        if (!errorEl) {
            return;
        }

        if (hasError) {
            errorEl.textContent = message;
            errorEl.classList.remove('hidden');

            return;
        }

        errorEl.textContent = '';
        errorEl.classList.add('hidden');
    };

    const clearRegisterFieldErrors = () => {
        [
            firstNameField,
            lastNameField,
            emailField,
            passwordField,
            passwordConfirmationField,
            countryField,
            descriptionField,
            dataTransferInput,
        ].forEach((field) => setFieldError(field));
    };

    const ensureStepVisible = (stepToShow) => {
        if (!step1 || !step2) {
            return;
        }

        if (stepToShow === 1) {
            step2.classList.add('hidden');
            step1.classList.remove('hidden');
            step1.removeAttribute('wfd-invisible');
            step1.querySelectorAll('[wfd-invisible]').forEach(el => el.removeAttribute('wfd-invisible'));

            return;
        }

        step1.classList.add('hidden');
        step2.classList.remove('hidden');
        step2.removeAttribute('wfd-invisible');
        step2.querySelectorAll('[wfd-invisible]').forEach(el => el.removeAttribute('wfd-invisible'));
    };

    const validateFieldGroup = (fields) => {
        const invalidField = fields.find((field) => {
            if (!field) {
                return false;
            }

            return !field.checkValidity();
        });

        if (!invalidField) {
            return true;
        }

        setFieldError(invalidField, invalidField.validationMessage || 'Revisa este campo.');
        invalidField.focus();

        return false;
    };

    const validateStep1 = () => validateFieldGroup(step1Fields);

    const validateStep2 = () => {
        const step2Fields = [dataTransferInput, descriptionField];

        return validateFieldGroup(step2Fields);
    };

    const applyStep1BusinessRules = () => {
        clearRegisterFieldErrors();

        if (!firstNameField?.value?.trim()) {
            setFieldError(firstNameField, 'Escribe tu nombre.');
        } else if (firstNameField.value.trim().length > 50) {
            setFieldError(firstNameField, 'El nombre no puede superar los 50 caracteres.');
        }

        if (!lastNameField?.value?.trim()) {
            setFieldError(lastNameField, 'Escribe tu apellido.');
        } else if (lastNameField.value.trim().length > 50) {
            setFieldError(lastNameField, 'El apellido no puede superar los 50 caracteres.');
        }

        if (!emailField?.value?.trim()) {
            setFieldError(emailField, 'Necesitamos tu correo electrónico.');
        } else if (emailField.value.length > 255) {
            setFieldError(emailField, 'El correo no puede superar 255 caracteres.');
        } else if (!emailField.checkValidity()) {
            setFieldError(emailField, 'Ese correo no es válido.');
        }

        if (!passwordField?.value) {
            setFieldError(passwordField, 'Crea una contraseña.');
        } else if (passwordField.value.length < 8) {
            setFieldError(passwordField, 'Tu contraseña debe tener al menos 8 caracteres.');
        }

        if (passwordConfirmationField && passwordField && passwordConfirmationField.value !== passwordField.value) {
            setFieldError(passwordConfirmationField, 'Las contraseñas no coinciden.');
        }

        if (countryField && !allowedCountries.has(countryField.value)) {
            setFieldError(countryField, 'Selecciona un país válido de la lista.');
        }
    };

    const applyStep2BusinessRules = () => {
        setFieldError(descriptionField);
        setFieldError(dataTransferInput);

        const descriptionValue = descriptionField?.value ?? '';
        if (descriptionValue.length > 500) {
            setFieldError(descriptionField, 'La descripción no puede superar los 500 caracteres.');
        }

        const photo = dataTransferInput?.files?.[0] ?? null;
        if (!photo) {
            setFieldError(dataTransferInput, 'La foto de perfil es obligatoria.');

            return;
        }

        const allowedMimeTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
        if (!allowedMimeTypes.has(photo.type)) {
            setFieldError(dataTransferInput, 'La foto de perfil debe ser JPG, PNG o WebP.');
        }

        const maxBytes = 2 * 1024 * 1024;
        if (photo.size > maxBytes) {
            setFieldError(dataTransferInput, 'La foto de perfil no puede superar los 2 MB.');
        }
    };

    const destroyCropper = () => {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    };

    const cleanupObjectUrl = () => {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    };

    const openImageModal = () => {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    };

    const closeImageModal = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    };

    const getCoverScaleFromImage = () => {
        if (!cropper) {
            return null;
        }
        const containerData = cropper.getContainerData();
        const imageData = cropper.getImageData();
        if (!containerData?.width || !containerData?.height || !imageData?.naturalWidth || !imageData?.naturalHeight) {
            return null;
        }

        return Math.max(containerData.width / imageData.naturalWidth, containerData.height / imageData.naturalHeight);
    };

    const applyCoverScale = (extraMargin = 0.05) => {
        const coverScale = getCoverScaleFromImage();
        if (!coverScale) {
            return null;
        }
        const targetScale = coverScale + extraMargin;
        cropper.zoomTo(targetScale);

        return targetScale;
    };

    const loadFileIntoCropper = (file) => {
        if (!file) {
            return;
        }

        destroyCropper();
        cleanupObjectUrl();

        objectUrl = URL.createObjectURL(file);
        imageToEdit.src = objectUrl;
        openImageModal();

        imageToEdit.onload = () => {
            destroyCropper();
            const CropperConstructor = resolveCropperConstructor();
            if (!CropperConstructor) {
                console.error('CropperJS no está disponible en window.Cropper');

                return;
            }

            cropper = new CropperConstructor(imageToEdit, {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 1,
                responsive: true,
                background: false,
                ready() {
                    applyCoverScale(0.05);
                },
            });
        };
    };

    if (mode === 'register' && goStep2 && step1 && step2) {
        goStep2.addEventListener('click', () => {
            applyStep1BusinessRules();
            if (!validateStep1()) {
                return;
            }

            ensureStepVisible(2);
        });
    }

    if (mode === 'register' && backStep1 && step1 && step2) {
        backStep1.addEventListener('click', () => {
            ensureStepVisible(1);
        });
    }

    openBtn.addEventListener('click', () => {
        if (cropSource === 'persistent' && cropSourceInput) {
            cropSourceInput.click();

            return;
        }

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';

        input.onchange = (event) => {
            const [file] = event.target.files || [];
            loadFileIntoCropper(file);
        };

        input.click();
    });

    if (cropSource === 'persistent' && cropSourceInput) {
        cropSourceInput.addEventListener('change', (event) => {
            const [file] = event.target.files || [];
            loadFileIntoCropper(file);
            cropSourceInput.value = '';
        });
    }

    resetBtn.addEventListener('click', () => {
        destroyCropper();
        cleanupObjectUrl();

        if (cropSource === 'persistent' && cropSourceInput) {
            cropSourceInput.click();

            return;
        }

        if (cropSource === 'dynamic') {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = (event) => {
                const [file] = event.target.files || [];
                loadFileIntoCropper(file);
            };
            input.click();
        }
    });

    applyBtn.addEventListener('click', () => {
        if (!cropper) {
            return;
        }

        const canvas = cropper.getCroppedCanvas({
            width: 300,
            height: 300,
            imageSmoothingQuality: 'high',
        });
        if (!canvas) {
            return;
        }

        const size = 300;
        const circleCanvas = document.createElement('canvas');
        circleCanvas.width = size;
        circleCanvas.height = size;
        const ctx = circleCanvas.getContext('2d');
        if (!ctx) {
            return;
        }

        ctx.clearRect(0, 0, size, size);
        ctx.drawImage(canvas, 0, 0, size, size);
        ctx.globalCompositeOperation = 'destination-in';
        ctx.beginPath();
        ctx.arc(size / 2, size / 2, size / 2, 0, Math.PI * 2);
        ctx.closePath();
        ctx.fill();
        ctx.globalCompositeOperation = 'source-over';

        const base64 = circleCanvas.toDataURL('image/png');
        base64Input.value = base64;
        preview.src = base64;
        preview.classList.remove('hidden');

        if (mode === 'profile') {
            closeImageModal();
            destroyCropper();
            cleanupObjectUrl();

            return;
        }

        circleCanvas.toBlob((blob) => {
            if (!blob || !dataTransferInput) {
                return;
            }

            const file = new File([blob], 'profile-cropped.png', { type: 'image/png' });
            const dt = new DataTransfer();
            dt.items.add(file);
            dataTransferInput.files = dt.files;

            closeImageModal();
            destroyCropper();
            cleanupObjectUrl();
        }, 'image/png');
    });

    cancelBtn.addEventListener('click', () => {
        closeImageModal();
        destroyCropper();
        cleanupObjectUrl();
    });

    if (mode === 'register' && form && dataTransferInput && step1 && step2 && openBtn) {
        form.addEventListener('submit', (event) => {
            applyStep1BusinessRules();
            if (!validateStep1()) {
                event.preventDefault();
                ensureStepVisible(1);

                return;
            }

            applyStep2BusinessRules();
            if (!validateStep2()) {
                event.preventDefault();
                ensureStepVisible(2);
                openBtn.focus();
            }
        });
    }

    if (mode === 'register') {
        [
            firstNameField,
            lastNameField,
            emailField,
            passwordField,
            passwordConfirmationField,
            countryField,
            descriptionField,
            dataTransferInput,
        ].forEach((field) => {
            if (!field) {
                return;
            }
            field.addEventListener('input', () => setFieldError(field));
            field.addEventListener('change', () => setFieldError(field));
        });
    }
}
