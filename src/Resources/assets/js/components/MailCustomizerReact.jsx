// src/Resources/assets/js/components/MailCustomizerReact.jsx

import React, { useState, useEffect } from 'react';
import axios from 'axios';

const MailCustomizerReact = ({ templateId, defaultVariables = { name: 'John Doe', email: 'john@example.com' } }) => {
  const [template, setTemplate] = useState({
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
  });
  const [variables, setVariables] = useState({...defaultVariables});
  const [showPreview, setShowPreview] = useState(false);
  const [previewContent, setPreviewContent] = useState('');
  
  useEffect(() => {
    if (templateId) {
      loadTemplate();
    }
  }, [templateId]);
  
  const loadTemplate = async () => {
    try {
      const response = await axios.get(`/api/mail-customizer/templates/${templateId}`);
      setTemplate(response.data);
    } catch (error) {
      console.error('Error loading template:', error);
    }
  };
  
  const saveTemplate = async () => {
    try {
      const url = templateId 
        ? `/api/mail-customizer/templates/${templateId}`
        : '/api/mail-customizer/templates';
        
      const method = templateId ? 'put' : 'post';
      
      const response = await axios[method](url, template);
      // Emit saved event if needed
    } catch (error) {
      console.error('Error saving template:', error);
    }
  };
  
  const preview = async () => {
    try {
      const response = await axios.post(`/api/mail-customizer/templates/${templateId}/preview`, variables);
      setPreviewContent(response.data.preview);
      setShowPreview(true);
    } catch (error) {
      console.error('Error generating preview:', error);
    }
  };
  
  const sendTest = async () => {
    try {
      const email = prompt('Enter email address for test:');
      if (email) {
        await axios.post(`/api/mail-customizer/templates/${templateId}/send-test`, {
          email,
          ...variables
        });
        alert('Test email sent successfully!');
      }
    } catch (error) {
      console.error('Error sending test email:', error);
    }
  };
  
  const updateTemplate = (field, value) => {
    setTemplate(prev => ({ ...prev, [field]: value }));
  };
  
  const updateStyle = (property, value) => {
    setTemplate(prev => ({
      ...prev,
      styles: {
        ...prev.styles,
        body: {
          ...prev.styles.body,
          [property]: value
        }
      }
    }));
  };
  
  const updateVariable = (key, value) => {
    setVariables(prev => ({ ...prev, [key]: value }));
  };
  
  return (
    <div className="mail-customizer">
      <div className="row">
        <div className="col-md-8">
          <div className="form-group">
            <label>Template Name</label>
            <input 
              type="text" 
              value={template.name} 
              onChange={(e) => updateTemplate('name', e.target.value)} 
              className="form-control" 
            />
          </div>
          
          <div className="form-group">
            <label>Email Subject</label>
            <input 
              type="text" 
              value={template.subject} 
              onChange={(e) => updateTemplate('subject', e.target.value)} 
              className="form-control" 
            />
          </div>
          
          <div className="form-group">
            <label>Email Content</label>
            <textarea 
              value={template.content} 
              onChange={(e) => updateTemplate('content', e.target.value)} 
              className="form-control" 
              rows="10" 
            />
          </div>
          
          <h4>Preview Variables</h4>
          <div className="row mb-3">
            {Object.keys(variables).map(key => (
              <div className="col-md-6" key={key}>
                <div className="form-group">
                  <label>{key}</label>
                  <input 
                    type="text" 
                    value={variables[key]} 
                    onChange={(e) => updateVariable(key, e.target.value)} 
                    className="form-control" 
                  />
                </div>
              </div>
            ))}
          </div>
        </div>
        
        <div className="col-md-4">
          <h4>Style Customization</h4>
          
          <div className="form-group">
            <label>Font Family</label>
            <select 
              value={template.styles.body['font-family']} 
              onChange={(e) => updateStyle('font-family', e.target.value)} 
              className="form-control"
            >
              <option value="Arial, sans-serif">Arial</option>
              <option value="Helvetica, sans-serif">Helvetica</option>
              <option value="Georgia, serif">Georgia</option>
              <option value="'Times New Roman', serif">Times New Roman</option>
            </select>
          </div>
          
          <div className="form-group">
            <label>Font Size</label>
            <input 
              type="text" 
              value={template.styles.body['font-size']} 
              onChange={(e) => updateStyle('font-size', e.target.value)} 
              className="form-control" 
            />
          </div>
          
          <div className="form-group">
            <label>Text Color</label>
            <input 
              type="color" 
              value={template.styles.body.color} 
              onChange={(e) => updateStyle('color', e.target.value)} 
              className="form-control" 
            />
          </div>
          
          <div className="form-group">
            <label>Background Color</label>
            <input 
              type="color" 
              value={template.styles.body['background-color']} 
              onChange={(e) => updateStyle('background-color', e.target.value)} 
              className="form-control" 
            />
          </div>
          
          <div className="form-group">
            <label>Layout</label>
            <select 
              value={template.layout} 
              onChange={(e) => updateTemplate('layout', e.target.value)} 
              className="form-control"
            >
              <option value="default">Default</option>
              <option value="sidebar">Sidebar</option>
              <option value="centered">Centered</option>
            </select>
          </div>
          
          <button onClick={preview} className="btn btn-info btn-block">Preview</button>
          <button onClick={saveTemplate} className="btn btn-primary btn-block">Save Template</button>
          <button onClick={sendTest} className="btn btn-warning btn-block">Send Test Email</button>
        </div>
      </div>
      
      {showPreview && (
        <div className="preview-modal">
          <div className="preview-content">
            <button className="close-preview" onClick={() => setShowPreview(false)}>×</button>
            <h4>Email Preview</h4>
            <div dangerouslySetInnerHTML={{ __html: previewContent }} />
          </div>
        </div>
      )}
    </div>
  );
};

export default MailCustomizerReact;