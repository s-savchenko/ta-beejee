<?php


namespace App\Controllers;


use App\Models\Task;

class TaskController extends Controller
{
    public function create()
    {
        $this->response->getBody()->write(
            $this->blade->render('task', ['task' => new Task()])
        );

        return $this->response;
    }

    public function store()
    {
        $task = new Task();
        $task = $this->fillTaskWithRequestData($task);

        if ($this->validate($task)) {
            $task->save();
            return $this->response->withHeader('Location', '/');
        }

        return $this->response;
    }

    private function fillTaskWithRequestData(Task &$task): Task
    {
        $parsedBody = $this->request->getParsedBody();

        $task->user_name = isset($parsedBody['user_name']) ? trim($parsedBody['user_name']) : '';
        $task->email = isset($parsedBody['email']) ? trim($parsedBody['email']) : '';
        $task->content = isset($parsedBody['content']) ? trim($parsedBody['content']) : '';

        return $task;
    }

    private function validate(Task $task): bool
    {
        $userNameFailed = $task->user_name === '';
        $emailFailed = !filter_var($task->email, FILTER_VALIDATE_EMAIL);
        $contentFailed = $task->content === '';

        $failed = $userNameFailed || $emailFailed || $contentFailed;

        if ($failed) {
            $isAuthenticated = $this->isAuthenticated();
            $this->response->getBody()->write(
                $this->blade->render('task',
                    compact('task', 'userNameFailed', 'emailFailed', 'contentFailed', 'isAuthenticated'))
            );
        }

        return !$failed;
    }

    public function edit()
    {
        if (!$this->isAuthenticated()) {
            return $this->response->withStatus(403);
        }

        $query = $this->request->getQueryParams();
        /** @var Task $task */
        $task = isset($query['id']) ? Task::find($query['id']) : null;
        if ($task) {
            $params = compact('task');
            $params['isAuthenticated'] = $this->isAuthenticated();
            $this->response->getBody()->write(
                $this->blade->render('task', $params)
            );
            return $this->response;
        } else {
            return $this->response->withStatus(404);
        }
    }

    public function update()
    {
        if (!$this->isAuthenticated()) {
            return $this->response->withStatus(403);
        }

        $query = $this->request->getQueryParams();
        /** @var Task $task */
        $task = isset($query['id']) ? Task::find($query['id']) : null;
        if ($task) {
            $task = $this->fillTaskWithRequestData($task);
            $parsedBody = $this->request->getParsedBody();
            if ($this->validate($task)) {
                $task->status = isset($parsedBody['done']) ? Task::STATUS_DONE : Task::STATUS_NEW;
                $task->save();
                return $this->response->withHeader('Location', '/');
            }
            return $this->response;
        } else {
            return $this->response->withStatus(404);
        }
    }
}