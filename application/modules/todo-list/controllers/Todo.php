<?php
class Todo extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With
    }



    public function getUsers()
    {
        echo json_encode($this->apilib->search('users', ["users_active = 1" ], null, 0, 'users_id', 'ASC'));    
    }



    public function getLists($user_id)
    {
        echo json_encode($this->apilib->search('fi_todo_lists', ["fi_todo_lists_created_by = ${user_id} OR fi_todo_lists_id IN (SELECT fi_todo_lists_id FROM rel_fi_todo_lists_users WHERE users_id = ${user_id})"], null, 0, 'fi_todo_lists_order', 'ASC'));    
    }


    
    public function changeListOrder()
    {
        $post = json_decode($this->input->post('listOrder'), true);

        if (empty($post['listId'])) {
            die(json_encode(['status' => 0, 'txt' => t('List not recognized')]));
        }
        if ($post['order'] == '') {
            die(json_encode(['status' => 0, 'txt' => t('List order not recognized')]));
        }
        if (empty($post['lists'])) {
            die(json_encode(['status' => 0, 'txt' => t('Lists not recognized')]));
        }

        $list_id = $post['listId'];
        $new_order = $post['order'];

        try {
            foreach ($post['lists'] as $pos => $order) {
                $this->db
                    ->where('fi_todo_lists_id', $order['fi_todo_lists_id'])
                    ->update('fi_todo_lists', [
                        'fi_todo_lists_order' => $pos + 1,
                ]);
            }
            echo json_encode(['status' => 1, 'data' => DB_BOOL_TRUE]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function createList()
    {
        $post = json_decode($this->input->post('newList'), true);

        if (empty($post['userId'])) {
            die(json_encode(['status' => 0, 'txt' => t('User not found')]));
        }
        if (empty($post['title'])) {
            die(json_encode(['status' => 0, 'txt' => t('You can\'t create a list without title.')]));
        }

        $created_by = $post['userId'];
        $title = $post['title'];

        try {
            $list = $this->apilib->create('fi_todo_lists', [
                'fi_todo_lists_name' => $title,
                'fi_todo_lists_created_by' => $created_by,
                'fi_todo_lists_order' => '0'
            ]);
            echo json_encode(['status' => 1, 'data' => $list]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function getTodoAssignedToMe($user_id)
    {
        echo json_encode($this->apilib->search('fi_todo', ['fi_todo_assigned_to' => $user_id], null, 0, 'fi_todo_order', 'ASC'));
    }



    public function getTodosList($list_id)
    {
        echo json_encode($this->apilib->search('fi_todo', ['fi_todo_list' => $list_id], null, 0, 'fi_todo_order', 'ASC'));
    }


    
    public function deleteList($list_id)
    {
        if (empty($list_id)) {
            die(json_encode(['status' => 0, 'txt' => t('List not recognized')]));
        }

        try {
            $deleted_list = $this->apilib->delete('fi_todo_lists', $list_id);
            echo json_encode(['status' => 1, 'deleted' => true]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function editCategory()
    {
        $post = json_decode($this->input->post('editedCategory'), true);

        if(empty($post['columnId'])) {
            die(json_encode(['status' => 0, 'txt' => t('Category not recognized')]));
        }
        if(empty($post['title'])) {
            die(json_encode(['status' => 0, 'txt' => t('Title is required')]));
        }

        $list_id = $post['columnId'];
        $title = $post['title'];

        try {
            $list = $this->apilib->edit('fi_todo_list_categories', $list_id, [
                'fi_todo_list_categories_name' => $title
            ]);
            echo json_encode(['status' => 1, 'data' => $list]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function deleteCategory()
    {
        $post = json_decode($this->input->post('editedCategory'), true);

        if(empty($post['columnId'])) {
            die(json_encode(['status' => 0, 'txt' => t('Category not recognized')]));
        }

        $list_id = $post['columnId'];


        try {
            $list = $this->apilib->view('fi_todo_list_categories', $list_id);

            if(!empty($list)) {       
                    $todos = $this->apilib->search("fi_todo", ["fi_todo_category" => $list["fi_todo_list_categories_id"], "fi_todo_deleted <> '1'"], null, 0, 'fi_todo_order', 'ASC');
                    if(!empty($todos)) {
                        try {
                            foreach ($todos as $todo) {
                                $this->apilib->edit('fi_todo', $todo['fi_todo_id'], [
                                    "fi_todo_category" => '0',
                                ]);
                            }
                            $deleted_list = $this->apilib->delete('fi_todo_list_categories', $list_id);
                            
                            echo json_encode(['status' => 1, 'editedTodosCategoryToZero' => true, 'column_id' => $list_id, 'todos' => $todos]);
                        } catch (Exception $e) {
                            log_message('error', $e->getMessage());
                            $error = json_encode([
                                'status' => 0,
                                'txt' => t('An error has occurred')
                            ]);
                            die($error);
                        }
                    } else {
                        $deleted_list = $this->apilib->delete('fi_todo_list_categories', $list_id);
                        echo json_encode(['status' => 1, 'deleted' => true, 'column_id' => $list_id]);
                    }
            } else {
                die(json_encode(['status' => 0, 'txt' => t('Category not found')]));
            }
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }
    
    

    public function changeTodoOrder()
    {
        $post = json_decode($this->input->post('todoOrder'), true);

        if (empty($post['todoId'])) {
            die(json_encode(['status' => 0, 'txt' => t('ToDo not recognized')]));
        }
        if ($post['order'] == '') {
            die(json_encode(['status' => 0, 'txt' => t('ToDo order not recognized')]));
        }
        if (empty($post['todos'])) {
            die(json_encode(['status' => 0, 'txt' => t('Todos not recognized')]));
        }

        $todo_id = $post['todoId'];
        $new_order = $post['order'];

        try {
            foreach ($post['todos'] as $pos => $order) {
                $this->db
                    ->where('fi_todo_id', $order['fi_todo_id'])
                    ->update('fi_todo', [
                        'fi_todo_order' => $pos + 1,
                ]);
            }
            echo json_encode(['status' => 1, 'data' => DB_BOOL_TRUE]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function toggleDoneTodo($todo_id, $done_status)
    {

        if(empty($todo_id)) {
            die(json_encode(['status' => 0, 'txt' => t('Todo not recognized')]));
        }
        if($done_status == '') {
            die(json_encode(['status' => 0, 'txt' => t('Done status not recognized')]));
        }

        $done_status = !$done_status;

        try {
            $todo = $this->apilib->edit('fi_todo', $todo_id, [
                'fi_todo_done' => $done_status
            ]);
            echo json_encode(['status' => 1, 'data' => $todo]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function setFavouriteTodo($todo_id, $starred_status)
    {
        if(empty($todo_id)) {
            die(json_encode(['status' => 0, 'txt' => t('Todo not recognized')]));
        }
        if($starred_status == '') {
            die(json_encode(['status' => 0, 'txt' => t('Starred status not recognized')]));
        }

        $starred_status = !$starred_status;

        try {
            $todo = $this->apilib->edit('fi_todo', $todo_id, [
                'fi_todo_starred' => $starred_status
            ]);
            echo json_encode(['status' => 1, 'response' => $todo]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function createTodo()
    {
        $post = json_decode($this->input->post('newTodo'), true);

        if (empty($post['userId'])) {
            die(json_encode(['status' => 0, 'txt' => t('User not found')]));
        }
        if (empty($post['listId'])) {
            die(json_encode(['status' => 0, 'txt' => t('List not found')]));
        }
        if (empty($post['text'])) {
            die(json_encode(['status' => 0, 'txt' => t('You can\'t create a todo without text')]));
        }

        $created_by = $post['userId'];
        $list_id = $post['listId'];
        $text = $post['text'];
        $category = $post['category'] ?? '';

        try {
            $todo = $this->apilib->create('fi_todo', [
                'fi_todo_text' => $text,
                'fi_todo_list' => $list_id,
                'fi_todo_created_by' => $created_by,
                'fi_todo_order' => 0,
                'fi_todo_done' => 0,
                'fi_todo_starred' => 0,
                'fi_todo_category' => $category,
            ]);
            echo json_encode(['status' => 1, 'data' => $todo]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function editTodo()
    {
        $post = json_decode($this->input->post('editedTodo'), true);
        $file = $_FILES['file'] ?? '';

        /* dump($file);
        //dump($this->input->post());
        exit; */

        if (empty($post['todoId'])) {
            die(json_encode(['status' => 0, 'txt' => t('Todo not recognized')]));
        }

        $todo_id = $post['todoId'];

        try {
            $todo = $this->apilib->edit('fi_todo', $todo_id, [
                'fi_todo_text' => $post['text'],
                'fi_todo_deadline' => $post['deadline'],
                'fi_todo_reminder' => $post['reminder'],
                'fi_todo_note' => $post['note'],    
                'fi_todo_category' => $post['category'],    
                'fi_todo_assigned_to' => $post['assignTo'],     
            ]);
            echo json_encode(['status' => 1, 'data' => $todo]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function deleteTodo($todo_id)
    {
        if (empty($todo_id)) {
            die(json_encode(['status' => 0, 'txt' => t('Todo not recognized')]));
        }

        try {
            $deleted_todo = $this->apilib->delete('fi_todo', $todo_id);
            echo json_encode(['status' => 1, 'deleted' => true]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function getListCategories($list_id)
    {
        if (empty($list_id)) {
            die(json_encode(['status' => 0, 'txt' => t('List not recognized')]));
        }

        $categories = $this->apilib->search("fi_todo_list_categories", ['fi_todo_list_categories_list_id' => $list_id], null, 0, 'fi_todo_list_categories_id', 'ASC');

        $return = [];
        if(!empty($categories)) {
            foreach ($categories as $category) {
                $return[] = [
                    'label' => $category['fi_todo_list_categories_name'],
                    'value' => $category['fi_todo_list_categories_id'],
                ];
            }
        }
        echo json_encode($return);
    }


    /**
     * 
     * Kanban mode
     * 
     */
    public function getTodoBySublist($list_id)
    {
        $current_userdata = $this->auth->getSessionUserdata();
        $user = $current_userdata['users_id'];
        $return = [];

        $columns = $this->apilib->search("fi_todo_list_categories", ['fi_todo_list_categories_list_id' => $list_id], null, 0, 'fi_todo_list_categories_id', 'ASC');

/*         if (empty($columns)) {
            die(json_encode($return));
        } */

        $uncategorized_todos = $this->apilib->search('fi_todo', ["fi_todo_list" => $list_id, "(fi_todo_category IS NULL OR fi_todo_category = '')", "(fi_todo_deleted <> '1')"], null, 0, 'fi_todo_order', 'ASC');
        if(!empty($uncategorized_todos)) {
            $return[] = [
                'title' => 'All todo',
                'columnId' => '0',
                'todos' => $uncategorized_todos
            ];
        }

        foreach ($columns as $column) {
            $todos = $this->apilib->search("fi_todo", ["fi_todo_category" => $column["fi_todo_list_categories_id"], "fi_todo_deleted <> '1'"], null, 0, 'fi_todo_order', 'ASC');

            $return[] = [
                'title' => $column['fi_todo_list_categories_name'],
                'columnId' => $column['fi_todo_list_categories_id'],
                'todos' => $todos
            ];
        }

        echo json_encode($return);
    }



    public function changeToDoCategory()
    {
        $post = json_decode($this->input->post('todoInfo'), true);

        if (empty($post['todo_id'])) {
            die(json_encode(['status' => 0, 'txt' => t('ToDo not found')]));
        }
        if (empty($post['column_id'])) {
            die(json_encode(['status' => 0, 'txt' => t('Category not found')]));
        }
        $todo_id = $post['todo_id'];
        $category_id = $post['column_id'];

        try {
            $todo = $this->apilib->searchFirst('fi_todo', ['fi_todo_id' => $todo_id]);
            if (empty($todo)) {
                $error = json_encode([
                    'status' => 0,
                    'txt' => t('ToDo not found')
                ]);
                die($error);
            }

            $response = $this->apilib->edit('fi_todo', $todo_id, [
                'fi_todo_category' => $category_id
            ]);
            echo json_encode(['status' => 1, 'data' => $response]);
        } catch (Exception $e) {
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }



    public function createCategory()
    {
        $post = json_decode($this->input->post('newCategory'), true);

        if (empty($post['list_id'])) {
            die(json_encode(['status' => 0, 'txt' => t('List not found')]));
        }
        if (empty($post['category_name'])) {
            die(json_encode(['status' => 0, 'txt' => t('No category name provided')]));
        }
        $list_id = $post['list_id'];
        $category_name = $post['category_name'];

        try {
            $list = $this->apilib->create('fi_todo_list_categories', [
                'fi_todo_list_categories_list_id' => $list_id,
                'fi_todo_list_categories_name' => $category_name,
            ]);
            echo json_encode(['status' => 1, 'data' => $list]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            $error = json_encode([
                'status' => 0,
                'txt' => t('An error has occurred')
            ]);
            die($error);
        }
    }

}