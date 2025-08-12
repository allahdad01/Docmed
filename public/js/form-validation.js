// Form Validation Module
// Handles form validation and error handling

export class FormValidator {
    constructor(formId, options = {}) {
        this.form = document.getElementById(formId);
        this.options = {
            validateOnInput: true,
            validateOnBlur: true,
            showErrors: true,
            errorClass: 'is-invalid',
            successClass: 'is-valid',
            errorMessageClass: 'invalid-feedback',
            ...options
        };
        
        this.rules = {};
        this.customValidators = {};
        this.errorMessages = {};
        
        if (this.form) {
            this.init();
        }
    }
    
    // Initialize the form validator
    init() {
        if (this.options.validateOnInput) {
            this.form.addEventListener('input', (e) => {
                this.validateField(e.target);
            });
        }
        
        if (this.options.validateOnBlur) {
            this.form.addEventListener('blur', (e) => {
                this.validateField(e.target);
            }, true);
        }
        
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Add validation rules for a field
    addRule(fieldName, rule, message = null) {
        if (!this.rules[fieldName]) {
            this.rules[fieldName] = [];
        }
        
        this.rules[fieldName].push({
            rule: rule,
            message: message
        });
    }
    
    // Add multiple rules for a field
    addRules(fieldName, rules) {
        rules.forEach(rule => {
            this.addRule(fieldName, rule.rule, rule.message);
        });
    }
    
    // Add custom validator
    addCustomValidator(fieldName, validator, message) {
        if (!this.customValidators[fieldName]) {
            this.customValidators[fieldName] = [];
        }
        
        this.customValidators[fieldName].push({
            validator: validator,
            message: message
        });
    }
    
    // Set custom error message for a field
    setErrorMessage(fieldName, message) {
        this.errorMessages[fieldName] = message;
    }
    
    // Validate a single field
    validateField(field) {
        const fieldName = field.name;
        if (!fieldName || !this.rules[fieldName]) return true;
        
        const value = this.getFieldValue(field);
        let isValid = true;
        let errorMessage = '';
        
        // Check built-in rules
        for (const ruleObj of this.rules[fieldName]) {
            if (!this.checkRule(ruleObj.rule, value, field)) {
                isValid = false;
                errorMessage = ruleObj.message || this.getDefaultErrorMessage(ruleObj.rule);
                break;
            }
        }
        
        // Check custom validators
        if (isValid && this.customValidators[fieldName]) {
            for (const customValidator of this.customValidators[fieldName]) {
                if (!customValidator.validator(value, field)) {
                    isValid = false;
                    errorMessage = customValidator.message;
                    break;
                }
            }
        }
        
        // Update field appearance
        this.updateFieldAppearance(field, isValid, errorMessage);
        
        return isValid;
    }
    
    // Validate entire form
    validateForm() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        let isValid = true;
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    // Check if a rule passes
    checkRule(rule, value, field) {
        const ruleParts = rule.split(':');
        const ruleName = ruleParts[0];
        const ruleValue = ruleParts[1];
        
        switch (ruleName) {
            case 'required':
                return this.isRequired(value);
            case 'email':
                return this.isEmail(value);
            case 'min':
                return this.isMinLength(value, parseInt(ruleValue));
            case 'max':
                return this.isMaxLength(value, parseInt(ruleValue));
            case 'minValue':
                return this.isMinValue(value, parseFloat(ruleValue));
            case 'maxValue':
                return this.isMaxValue(value, parseFloat(ruleValue));
            case 'pattern':
                return this.matchesPattern(value, ruleValue);
            case 'phone':
                return this.isPhone(value);
            case 'url':
                return this.isUrl(value);
            case 'numeric':
                return this.isNumeric(value);
            case 'integer':
                return this.isInteger(value);
            case 'decimal':
                return this.isDecimal(value);
            case 'date':
                return this.isDate(value);
            case 'future':
                return this.isFutureDate(value);
            case 'past':
                return this.isPastDate(value);
            case 'file':
                return this.isValidFile(field, ruleValue);
            default:
                return true;
        }
    }
    
    // Get field value
    getFieldValue(field) {
        if (field.type === 'checkbox') {
            return field.checked;
        } else if (field.type === 'radio') {
            const checkedRadio = field.closest('fieldset')?.querySelector(`input[name="${field.name}"]:checked`);
            return checkedRadio ? checkedRadio.value : '';
        } else if (field.type === 'file') {
            return field.files;
        } else {
            return field.value.trim();
        }
    }
    
    // Update field appearance based on validation result
    updateFieldAppearance(field, isValid, errorMessage) {
        if (!this.options.showErrors) return;
        
        // Remove existing classes
        field.classList.remove(this.options.errorClass, this.options.successClass);
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector(`.${this.options.errorMessageClass}`);
        if (existingError) {
            existingError.remove();
        }
        
        if (isValid) {
            field.classList.add(this.options.successClass);
        } else {
            field.classList.add(this.options.errorClass);
            
            // Add error message
            if (errorMessage) {
                const errorDiv = document.createElement('div');
                errorDiv.className = this.options.errorMessageClass;
                errorDiv.textContent = errorMessage;
                field.parentNode.appendChild(errorDiv);
            }
        }
    }
    
    // Validation methods
    isRequired(value) {
        if (typeof value === 'boolean') {
            return value === true;
        }
        return value !== null && value !== undefined && value.toString().trim() !== '';
    }
    
    isEmail(value) {
        if (!value) return true; // Skip if empty (use required rule for that)
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(value);
    }
    
    isMinLength(value, minLength) {
        if (!value) return true;
        return value.toString().length >= minLength;
    }
    
    isMaxLength(value, maxLength) {
        if (!value) return true;
        return value.toString().length <= maxLength;
    }
    
    isMinValue(value, minValue) {
        if (!value) return true;
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue >= minValue;
    }
    
    isMaxValue(value, maxValue) {
        if (!value) return true;
        const numValue = parseFloat(value);
        return !isNaN(numValue) && numValue <= maxValue;
    }
    
    matchesPattern(value, pattern) {
        if (!value) return true;
        const regex = new RegExp(pattern);
        return regex.test(value);
    }
    
    isPhone(value) {
        if (!value) return true;
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        return phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''));
    }
    
    isUrl(value) {
        if (!value) return true;
        try {
            new URL(value);
            return true;
        } catch {
            return false;
        }
    }
    
    isNumeric(value) {
        if (!value) return true;
        return !isNaN(parseFloat(value));
    }
    
    isInteger(value) {
        if (!value) return true;
        return Number.isInteger(parseFloat(value));
    }
    
    isDecimal(value) {
        if (!value) return true;
        return !isNaN(parseFloat(value)) && value.toString().includes('.');
    }
    
    isDate(value) {
        if (!value) return true;
        const date = new Date(value);
        return !isNaN(date.getTime());
    }
    
    isFutureDate(value) {
        if (!value) return true;
        const date = new Date(value);
        const now = new Date();
        return date > now;
    }
    
    isPastDate(value) {
        if (!value) return true;
        const date = new Date(value);
        const now = new Date();
        return date < now;
    }
    
    isValidFile(field, allowedTypes) {
        if (!field.files || field.files.length === 0) return true;
        
        const file = field.files[0];
        const allowedTypesArray = allowedTypes.split(',').map(type => type.trim());
        
        return allowedTypesArray.some(type => {
            if (type.startsWith('.')) {
                return file.name.toLowerCase().endsWith(type.toLowerCase());
            } else {
                return file.type.startsWith(type);
            }
        });
    }
    
    // Get default error message for a rule
    getDefaultErrorMessage(rule) {
        const ruleParts = rule.split(':');
        const ruleName = ruleParts[0];
        const ruleValue = ruleParts[1];
        
        const messages = {
            required: 'This field is required',
            email: 'Please enter a valid email address',
            min: `Minimum length is ${ruleValue} characters`,
            max: `Maximum length is ${ruleValue} characters`,
            minValue: `Minimum value is ${ruleValue}`,
            maxValue: `Maximum value is ${ruleValue}`,
            pattern: 'Please match the required format',
            phone: 'Please enter a valid phone number',
            url: 'Please enter a valid URL',
            numeric: 'Please enter a valid number',
            integer: 'Please enter a whole number',
            decimal: 'Please enter a decimal number',
            date: 'Please enter a valid date',
            future: 'Please enter a future date',
            past: 'Please enter a past date',
            file: `Please select a valid file type (${ruleValue})`
        };
        
        return messages[ruleName] || 'Invalid value';
    }
    
    // Reset form validation
    reset() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        fields.forEach(field => {
            field.classList.remove(this.options.errorClass, this.options.successClass);
            
            const existingError = field.parentNode.querySelector(`.${this.options.errorMessageClass}`);
            if (existingError) {
                existingError.remove();
            }
        });
    }
    
    // Get validation errors
    getErrors() {
        const errors = {};
        const fields = this.form.querySelectorAll('input, select, textarea');
        
        fields.forEach(field => {
            if (field.classList.contains(this.options.errorClass)) {
                const errorMessage = field.parentNode.querySelector(`.${this.options.errorMessageClass}`);
                errors[field.name] = errorMessage ? errorMessage.textContent : 'Invalid value';
            }
        });
        
        return errors;
    }
    
    // Check if form has errors
    hasErrors() {
        return Object.keys(this.getErrors()).length > 0;
    }
}

// Utility functions for form validation
export function validateRequired(value) {
    return value !== null && value !== undefined && value.toString().trim() !== '';
}

export function validateEmail(email) {
    if (!email) return true;
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

export function validatePhone(phone) {
    if (!phone) return true;
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}

export function validateUrl(url) {
    if (!url) return true;
    try {
        new URL(url);
        return true;
    } catch {
        return false;
    }
}

export function validateNumeric(value) {
    if (!value) return true;
    return !isNaN(parseFloat(value));
}

export function validateInteger(value) {
    if (!value) return true;
    return Number.isInteger(parseFloat(value));
}

export function validateMinLength(value, minLength) {
    if (!value) return true;
    return value.toString().length >= minLength;
}

export function validateMaxLength(value, maxLength) {
    if (!value) return true;
    return value.toString().length <= maxLength;
}

export function validateRange(value, min, max) {
    if (!value) return true;
    const numValue = parseFloat(value);
    return !isNaN(numValue) && numValue >= min && numValue <= max;
}

export function validatePattern(value, pattern) {
    if (!value) return true;
    const regex = new RegExp(pattern);
    return regex.test(value);
}

export function validateDate(value) {
    if (!value) return true;
    const date = new Date(value);
    return !isNaN(date.getTime());
}

export function validateFutureDate(value) {
    if (!value) return true;
    const date = new Date(value);
    const now = new Date();
    return date > now;
}

export function validatePastDate(value) {
    if (!value) return true;
    const date = new Date(value);
    const now = new Date();
    return date < now;
}

// Quick validation helpers
export const validators = {
    required: validateRequired,
    email: validateEmail,
    phone: validatePhone,
    url: validateUrl,
    numeric: validateNumeric,
    integer: validateInteger,
    minLength: validateMinLength,
    maxLength: validateMaxLength,
    range: validateRange,
    pattern: validatePattern,
    date: validateDate,
    futureDate: validateFutureDate,
    pastDate: validatePastDate
};