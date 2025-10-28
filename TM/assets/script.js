/* ---------- PERSONAL REPORT (download txt) ---------- */
function personalReport(person){
  if(!person) {
    alert('Please enter a person name');
    return;
  }
  
  // Fetch tasks from PHP backend
  fetch('api.php?action=getAllTasks')
    .then(r => r.json())
    .then(assignments => {
      const filtered = assignments.filter(a => a.to === person || a.from === person);
      if(!filtered.length) {
        alert('No tasks for ' + person);
        return;
      }
      const lines = filtered.map(a => a.title + ' | ' + a.status + ' | ' + (a.to === person ? 'From' : 'To') + ' ' + (a.to === person ? a.from : a.to) + ' | Due: ' + formatDate(a.dueDate));
      const txt = lines.join('\n');
      const blob = new Blob([txt], {type:'text/plain'});
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = person.replace(/\s+/g,'_') + '_report.txt';
      link.click();
      URL.revokeObjectURL(url);
      alert('Report for ' + person + ' downloaded!');
    })
    .catch(err => {
      console.error('Error:', err);
      alert('Failed to generate report');
    });
}

function formatDate(d) {
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US', {year:'numeric', month:'short', day:'numeric'});
}
