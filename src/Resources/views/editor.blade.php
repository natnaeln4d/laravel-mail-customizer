{{-- src/Resources/views/form.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <title>Mail Customizer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/vue@2.6.14/dist/vue.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <style>
        .preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .preview-content {
            background: white;
            padding: 20px;
            border-radius: 5px;
            max-width: 80%;
            max-height: 80%;
            overflow: auto;
            position: relative;
        }
        .close-preview {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div id="mail-customizer-app" class="container mt-4">
        <mail-customizer 
            :template-id="{{ $templateId ?? 'null' }}"
            :default-variables="{{ json_encode($variables ?? ['name' => 'John Doe', 'email' => 'john@example.com']) }}"
            @saved="onSaved"
        ></mail-customizer>
    </div>

    <script>
        // Register the Vue component
        Vue.component('mail-customizer', {
            template: `@include('mail-customizer::MailCustomizer.vue')`,
            props: {
                templateId: {
                    type: Number,
                    default: null
                },
                defaultVariables: {
                    type: Object,
                    default: () => ({ name: 'John Doe', email: 'john@example.com' })
                }
            },
            data() {
                return {
                    template: {
                        name: '',
                        subject: '',
                        content: '',
                        styles: {
                            body: {
                                'font-family': 'Arial, sans-serif',
                                'font-size': '14px',
                                color: '#333333',
                                'background-color': '#ffffff'
                            }
                        },
                        layout: 'default'
                    },
                    variables: {...this.defaultVariables},
                    showPreview: false,
                    previewContent: ''
                }
            },
            mounted() {
                if (this.templateId) {
                    this.loadTemplate();
                }
            },
            methods: {
                async loadTemplate() {
                    try {
                        const response = await axios.get(`/api/mail-customizer/templates/${this.templateId}`);
                        this.template = response.data;
                    } catch (error) {
                        console.error('Error loading template:', error);
                    }
                },
                async saveTemplate() {
                    try {
                        const url = this.templateId 
                            ? `/api/mail-customizer/templates/${this.templateId}`
                            : '/api/mail-customizer/templates';
                            
                        const method = this.templateId ? 'put' : 'post';
                        
                        const response = await axios[method](url, this.template);
                        this.$emit('saved', response.data);
                    } catch (error) {
                        console.error('Error saving template:', error);
                    }
                },
                async preview() {
                    try {
                        const response = await axios.post(`/api/mail-customizer/templates/${this.templateId}/preview`, this.variables);
                        this.previewContent = response.data.preview;
                        this.showPreview = true;
                    } catch (error) {
                        console.error('Error generating preview:', error);
                    }
                },
                async sendTest() {
                    try {
                        const email = prompt('Enter email address for test:');
                        if (email) {
                            await axios.post(`/api/mail-customizer/templates/${this.templateId}/send-test`, {
                                email,
                                ...this.variables
                            });
                            alert('Test email sent successfully!');
                        }
                    } catch (error) {
                        console.error('Error sending test email:', error);
                    }
                }
            }
        });

        new Vue({
            el: '#mail-customizer-app',
            methods: {
                onSaved(template) {
                    alert('Template saved successfully!');
                    console.log('Template saved:', template);
                }
            }
        });
    </script>
</body>
</html>