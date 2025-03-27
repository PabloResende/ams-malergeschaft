<?php

require_once __DIR__ . '/../models/Project.php';

class ProjectController
{
    public function index()
    {
        $projects = Project::all();
        require __DIR__ . '/../../views/projects/index.php';
    }

    public function create()
    {
        require __DIR__ . '/../../views/projects/create.php';
    }

    public function store()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $name = $_POST['name'];
            $description = $_POST['description'];
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];
            $total_hours = $_POST['total_hours'];

            Project::create($name, $description, $start_date, $end_date, $total_hours);
            header("Location: /dashboard");
            exit;
        }
    }
}
