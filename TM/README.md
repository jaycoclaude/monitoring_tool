# TaskFlow - Task Management System

A functional task management system with PHP backend and MySQL database.

## Features

- **Dashboard**: View all tasks with filtering by All, Received, and Sent
- **Search**: Functional search across all assignments
- **Create Tasks**: Create new assignments with full form handling and file attachments
- **File Attachments**: Upload and download multiple files per task (PDF, DOC, DOCX, TXT, JPG, PNG, ZIP)
- **View Details**: View individual task details and update status
- **Personal Reports**: Generate downloadable reports for any person
- **Persistent Storage**: Tasks are stored in MySQL database with full CRUD operations

## Files Structure

### PHP Files (Backend)
- `index.php` - Main dashboard with tabs and search
- `create.php` - Create new assignments with file upload support
- `view.php` - View and update task details
- `download.php` - File download handler for attachments
- `data.php` - Data management functions with database operations
- `api.php` - API endpoint for AJAX requests
- `config.php` - Database configuration
- `install.php` - Database installation script

### Static Files
- `assets/style.css` - All styling
- `assets/script.js` - Client-side JavaScript for personal reports

### Directories
- `uploads/` - Directory for storing uploaded files

### Database
- MySQL database `task_system` with `tasks` table

## Installation

1. **Setup Database**:
   - Make sure MySQL is running in XAMPP
   - Navigate to `http://localhost/task-system/install.php` in your browser
   - This will create the database and table automatically
   - Sample data will be inserted if the table is empty

2. **Configure Database** (if needed):
   - Edit `config.php` to change database credentials

## How It Works

1. **Task Creation**: When you create a task in `create.php`, it's saved to the MySQL `tasks` table
2. **Task Display**: All pages fetch tasks from the database via `data.php` functions
3. **CRUD Operations**:
   - **Create**: `addTask()` - Insert new tasks
   - **Read**: `getTasks()`, `getTaskById()` - Retrieve tasks
   - **Update**: `updateTaskStatus()`, `updateTask()` - Update task data
   - **Delete**: `deleteTask()` - Remove tasks
4. **Tabs**:
   - **All**: Shows all tasks
   - **Received**: Shows tasks where current user is the recipient
   - **Sent**: Shows tasks where current user is the sender
5. **Search**: Server-side search filters tasks by title, description, from, and to
6. **Status Updates**: Status can be updated in `view.php` and changes are persisted to the database

## Usage

1. **First Time Setup**: Run the installation script at `http://localhost/task-system/install.php`
2. Access the system through your web browser: `http://localhost/task-system/index.php`
3. Create tasks by clicking "+ New Assignment"
   - Fill in task details
   - Optionally upload multiple files as attachments (PDF, DOC, DOCX, TXT, JPG, PNG, ZIP)
4. Use tabs to filter tasks
5. Search using the search box
6. Click any task to view details and update status
7. Download attachments by clicking on file names in task details
8. Generate personal reports by entering a person's name

## File Attachments

- **Supported Formats**: PDF, DOC, DOCX, TXT, JPG, JPEG, PNG, ZIP
- **Multiple Files**: You can upload multiple files per task
- **Storage**: Files are stored in the `uploads/` directory with timestamped names to prevent conflicts
- **Security**: File types are validated on upload
- **Download**: Files can be downloaded from the task details page

## Current User

Default user is "Alice Admin". You can change this in `data.php` in the `getCurrentUser()` function.

