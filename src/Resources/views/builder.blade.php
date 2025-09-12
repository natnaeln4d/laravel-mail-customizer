{{-- src/Resources/views/builder.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Email Template Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .builder-container {
            margin: 20px;
        }
        /* Add styles from the Vue component */
    </style>
</head>
<body>
    <div class="builder-container">
        <h1>Email Template Builder</h1>
        
        <div id="mail-builder-app">
            <mail-builder 
                :template-id="{{ $templateId ?? 'null' }}"
                :default-variables="{{ json_encode($variables ?? []) }}"
            ></mail-builder>
            
            <div class="mt-3">
                <button class="btn btn-primary" @click="saveTemplate">Save Template</button>
                <button class="btn btn-secondary" @click="previewTemplate">Preview</button>
                <button class="btn btn-success" @click="sendTest">Send Test</button>
            </div>
        </div>
    </div>

    <script src="{{ asset('vendor/mail-customizer/js/mail-builder.js') }}"></script>
    <script>
        new Vue({
            el: '#mail-builder-app',
            components: {
                'mail-builder': MailBuilderVue
            },
            methods: {
                async saveTemplate() {
                    const builder = this.$children[0];
                    const templateData = builder.getTemplateData();
                    
                    try {
                        const url = builder.templateId 
                            ? `/api/mail-customizer/templates/${builder.templateId}`
                            : '/api/mail-customizer/templates';
                            
                        const method = builder.templateId ? 'put' : 'post';
                        
                        const response = await axios[method](url, {
                            name: prompt('Template name:', 'New Template'),
                            subject: prompt('Email subject:', 'New Email'),
                            ...templateData
                        });
                        
                        alert('Template saved successfully!');
                    } catch (error) {
                        console.error('Error saving template:', error);
                        alert('Error saving template');
                    }
                },
                async previewTemplate() {
                    const builder = this.$children[0];
                    const templateData = builder.getTemplateData();
                    
                    try {
                        const response = await axios.post('/api/mail-customizer/templates/preview', templateData);
                        
                        const previewWindow = window.open('', '_blank');
                        previewWindow.document.write(response.data.preview);
                    } catch (error) {
                        console.error('Error generating preview:', error);
                    }
                },
                async sendTest() {
                    const email = prompt('Enter email address for test:');
                    if (email) {
                        const builder = this.$children[0];
                        const templateData = builder.getTemplateData();
                        
                        try {
                            await axios.post('/api/mail-customizer/templates/send-test', {
                                email: email,
                                ...templateData
                            });
                            alert('Test email sent successfully!');
                        } catch (error) {
                            console.error('Error sending test email:', error);
                            alert('Error sending test email');
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>