<!DOCTYPE html>
<html>
<head>
  <title>Attendance Template Designer</title>
  <script src="https://cdn.ckeditor.com/4.20.1/standard/ckeditor.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html-docx-js/0.3.1/html-docx.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
  <style>
    body { display: flex; gap: 20px; font-family: Arial; }
    #editorContainer, #previewContainer { width: 50%; }
    iframe { width: 100%; height: 80vh; border: 1px solid #ccc; background: #fff; }
    button { margin-top: 10px; padding: 8px 12px; cursor: pointer; }
  </style>
</head>
<body>
  <div id="editorContainer">
    <h3>🎨 Design Your Attendance Template</h3>
    <textarea id="editor">
      <h2 style="text-align:center;">Attendance List</h2>
      <table border="1" cellspacing="0" cellpadding="5" style="width:100%; border-collapse:collapse;">
        <tr>
          <th>No.</th>
          <th>Name</th>
          <th>Department</th>
          <th>Date</th>
          <th>Signature</th>
        </tr>
        <tr><td>1</td><td>Jean-Claude</td><td>ICT</td><td>30/10/2025</td><td>________</td></tr>
      </table>
    </textarea>
    <button onclick="updatePreview()">👁️ Preview</button>
    <button onclick="downloadDoc()">⬇️ Download as Word</button>
  </div>

  <div id="previewContainer">
    <h3>🖼️ Live Preview</h3>
    <iframe id="preview"></iframe>
  </div>

  <script>
    CKEDITOR.replace('editor');

    function updatePreview() {
      const html = CKEDITOR.instances.editor.getData();
      const preview = document.getElementById('preview').contentWindow.document;
      preview.open();
      preview.write(html);
      preview.close();
    }

    function downloadDoc() {
      const html = CKEDITOR.instances.editor.getData();
      const converted = htmlDocx.asBlob('<html><body>' + html + '</body></html>');
      saveAs(converted, 'attendance_template.docx');
    }
  </script>
</body>
</html>
