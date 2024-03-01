Vue.component("draggable", Vue.Draggable);

Vue.component("v-select", VueSelect.VueSelect);

//const { vue2Dropzone } = window.vue2Dropzone;
//console.log(window.vue2Dropzone);
Vue.component("vue-dropzone", window.vue2Dropzone);

Vue.component("TodoInput", {
    template: `
        <div v-if="showTodo && (column == currentTodo)" class="mb-6">
            <input type="text"
                v-model="newTodoTitle"
                class="p-4 mt-4 pr-12 text-md bg-gray-100 rounded-lg shadow-sm fixed-kanban-column-width"
                placeholder="Inserisci titolo todo"
                @keyup.enter="createTodo"
            >
        </div>
    `,
    props: ["show-todo", "column", "current-todo"],
    data() {
        return {
            newTodoTitle: "",
        };
    },
    methods: {
        createTodo() {
            if (this.newTodoTitle.length != 0) {
                //console.log('colonna in cui aggiungerlo: ', this.column)
                this.$emit("add-todo-to-list", { todoText: this.newTodoTitle, column: this.column });
                this.newTodoTitle = "";
            }
        },
    },
});

Vue.config.devtools = true;

var app = new Vue({
    el: "#app",
    data() {
        return {
            loggedUser: userLogged,
            listMode: true,
            kanbanMode: false,
            lists: [],
            sharedLists: [],
            selectedList: null,
            selectedListName: "",
            selectedListObject: null,
            editListUrl: "",
            deleteListUrl: "",
            todos: [],
            doneTodos: [],
            todosByCategory: [],
            selectedTodo: null,
            isLoadingLists: false,
            isLoadingTodos: false,
            errorMessage: null,
            error: false,
            newList: "",
            newTodo: "",
            showSidebar: false,
            //select for todo category in sidebar
            listCategories: [],
            //Kanban only data
            showNewCategoryName: false,
            newCategoryName: "",
            //modal field
            showModal: false,
            //kanabn input
            showInput: false,
            currentTodo: null,
            //search query for searching in todo title
            searchQuery: "",
            //permission to edit/delete list only for the creator
            canModifyList: false,
            //permission to edit/delete todo only for the creator
            canModifyTodo: false,
            //show/hide category or todo form in sideabr
            editingCategory: false,
            selectedCategory: null,
            //select user in edit todo (ASSIGNED USER)
            selectedUser: null,
            allUsers: [],
            // todo assigned to me
            assignedToMe: [],
            assignedToMeDone: [],
            assignedToMeMode: false,
            baseUrl: base_url,
            fileToUpload: null,
            fileName: null,
            //show / hide list container
            showListContainer: true,
            listContainer: null,
            //show / hide done todos in list mode
            doneTodosVisible: true,
            disabledInput: false,
        };
    },
    component: {
        DatePicker,
        //VueDropzone: vue2Dropzone,
    },
    methods: {
        toggleShowListContainer() {
            console.log(this.$refs.listContainer);
            this.showListContainer = !this.showListContainer;
        },

        /**
         * ! Show / hide done todos in list mode
         */
        toggleDoneTodoVisibility() {
            this.doneTodosVisible = !this.doneTodosVisible;
        },
        /**
         * !Dropzone methods
         */
        setUrl() {
            return `${base_url}todo-list/todo/saveAttachments`;
        },
        /************************************************************************************************************** */
        /**
         * Get all users
         */
        getAllUsers() {
            var self = this;

            const params = new URLSearchParams();
            params.append([token_name], token_hash);

            try {
                axios.post(`${base_url}todo-list/todo/getUsers/`, params).then(function (response) {
                    self.allUsers = response.data;
                });
            } catch (error) {
                console.error(error);
            }
        },

        addToList(todo) {
            if (todo) {
                //cerco todo modificato e aggiorno il valore di order
                const columnToUpdate = this.todosByCategory.find((category) => category.columnId == todo.column);
                if (columnToUpdate) {
                    var self = this;
                    var data = {
                        userId: this.loggedUser,
                        listId: this.selectedList,
                        text: todo.todoText,
                        category: todo.column,
                    };

                    const params = new URLSearchParams();
                    params.append([token_name], token_hash);
                    params.append("newTodo", JSON.stringify(data));

                    try {
                        axios.post(`${base_url}todo-list/todo/createTodo/`, params).then(function (response) {
                            //console.log(response);
                            if (response.data.status == 1) {
                                columnToUpdate.todos.unshift(response.data.data);
                                //devo aggiungerlo anche all'array della list
                                self.todos.unshift(response.data.data);
                                //chiudo e svuoto input colonna kanban
                                self.showInput = false;
                                self.currentTodo = null;
                                self.removeError();
                            } else {
                                console.log(self.error);
                                self.error = true;
                                self.errorMessage = response.data.txt;
                            }
                        });
                    } catch (error) {
                        console.error(error);
                    }
                }
            }
        },
        /********************************************
         *
         * UTILITIES
         *
         *********************************************/
        toggleShowModal() {
            this.showModal = !this.showModal;
        },
        /**
         * ! Print user list if a list is shared with someone
         */
        printUsers(users) {
            return Object.values(users);
        },
        /**
         * ! Set mode on tabs click
         */
        setMode(mode) {
            localStorage.setItem("todoMode", mode);
            if (mode === "listMode") {
                this.listMode = true;
                this.kanbanMode = false;
                this.assignedToMeMode = false;
            } else {
                this.kanbanMode = true;
                this.listMode = false;
                this.assignedToMeMode = false;
            }
            //Reset kanban only fields
            this.newCategoryName = "";
            this.showNewCategoryName = false;
        },
        /**
         * ! Close sidebar on icon click
         * * Set showSidebar to false
         */
        handleCloseSidebar(value) {
            //console.log(value);
            this.showSidebar = value;
        },
        /**
         * ! Remove error on icon click
         */
        removeError() {
            this.error = false;
            this.errorMessage = null;
        },
        /**
         * ! Format date to d/m/Y for todo Board visualization
         */
        formatDate(date) {
            if (date && moment.isDate(new Date(date))) {
                return moment(date).format("DD/MM/YYYY");
            } else {
                return "-";
            }
        },
        /**
         * ! Format date to d/m/Y HH:mm for todo Board visualization
         */
        formatDateWithHours(date) {
            if (date && moment.isDate(new Date(date))) {
                return moment(date).format("DD/MM/YYYY HH:mm");
            } else {
                return "-";
            }
        },
        /**
         * ! Set icon color based on expiration date
         */
        isExpired(date) {
            const todoDeadlineDate = moment(date);
            const todayDate = moment();

            if (todoDeadlineDate.diff(todayDate) < 0) {
                return "text-red-800 bg-red-100";
            } else {
                return "text-blue-800 bg-blue-100";
            }
        },

        currentTodoToShow(columnId) {
            this.showInput = !this.showInput;
            this.currentTodo = columnId;
        },
        /**
         * ! Show sidebar to edit category title
         */
        showEditCategory(columnId) {
            if (columnId == "0") {
                return;
            }
            //Get data for the selected column
            const category = this.todosByCategory.find((list) => list.columnId === columnId);
            if (category) {
                this.selectedTodo = null;
                this.showSidebar = true;
                this.editingCategory = true;
                this.selectedCategory = {
                    ...category,
                };
            }
        },
        /**
         * ! Edit category (column) title
         */
        editCategory() {
            if (this.selectedCategory && this.selectedCategory.title.length > 0) {
                const newTitle = this.selectedCategory.title;
                const columnId = this.selectedCategory.columnId;

                var self = this;
                var data = {
                    title: newTitle,
                    columnId: columnId,
                };

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append("editedCategory", JSON.stringify(data));

                try {
                    axios.post(`${base_url}todo-list/todo/editCategory/`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status === 1) {
                            const column = self.todosByCategory.find((list) => list.columnId === response.data.data.fi_todo_list_categories_id);
                            if (column) {
                                column.title = response.data.data.fi_todo_list_categories_name;
                            }
                            //Edit category for todo in List view
                            self.todos.forEach((todo) => {
                                if (todo.fi_todo_category == response.data.data.fi_todo_list_categories_id) {
                                    todo.fi_todo_category = response.data.data.fi_todo_list_categories_name;
                                }
                            });
                            self.doneTodos.forEach((todo) => {
                                if (todo.fi_todo_category == response.data.data.fi_todo_list_categories_id) {
                                    todo.fi_todo_category = response.data.data.fi_todo_list_categories_name;
                                }
                            });
                            //Close sidebar
                            self.showSidebar = false;
                            //self.selectedCategory = null;
                        }
                    });
                } catch (error) {
                    console.error(error);
                }
            }
        },
        /**
         * ! Delete single column and set its todo without category
         */
        deleteCategory(columnId) {
            if (columnId) {
                var self = this;
                var data = {
                    columnId: columnId,
                };

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append("editedCategory", JSON.stringify(data));

                try {
                    axios.post(`${base_url}todo-list/todo/deleteCategory/`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status === 1) {
                            if (response.data.editedTodosCategoryToZero) {
                                //Remove column
                                self.todosByCategory = self.todosByCategory.filter((category) => category.columnId != response.data.column_id);
                                //Move todo in "All Todo" column
                                const allTodo = self.todosByCategory.find((list) => list.columnId === "0");
                                if (allTodo) {
                                    response.data.todos.forEach((todo) => {
                                        allTodo.todos.push(todo);
                                    });
                                } else {
                                    //Create "All Todo" column
                                    self.todosByCategory.unshift({
                                        title: "All todo",
                                        columnId: "0",
                                        todos: new Array(response.data.todos),
                                    });
                                }
                                //Remove cateogry from todo in List view
                                self.todos.forEach((todo) => {
                                    if (todo.fi_todo_category == response.data.column_id) {
                                        todo.fi_todo_category = null;
                                    }
                                });
                                self.doneTodos.forEach((todo) => {
                                    if (todo.fi_todo_category == response.data.column_id) {
                                        todo.fi_todo_category = null;
                                    }
                                });
                                self.showSidebar = false;
                                //self.selectedCategory = null;
                            } else if (response.data.deleted) {
                                //Remove column
                                self.todosByCategory = self.todosByCategory.filter((category) => category.columnId != response.data.column_id);
                                self.showSidebar = false;
                                //self.selectedCategory = null;
                            }
                        }
                    });
                } catch (error) {
                    console.error(error);
                }
            }
        },
        /********************************************
         *
         * LIST MODE
         *
         *********************************************/
        /**
         * ! Get todo assigned to me
         */
        getAssignedToMe() {
            this.isLoadingLists = true;
            this.assignedToMe = [];
            this.assignedToMeDone = [];

            var self = this;
            try {
                const req = fetch(`${base_url}/todo-list/todo/getTodoAssignedToMe/${self.loggedUser}`)
                    .then((response) => response.json())
                    .then((data) => {
                        console.log(data);
                        if (data.length != 0) {
                            /**
                             * ? Ordino i todo in base ad order e poi li inserisco negli array corretti
                             */
                            data.sort((a, b) => parseInt(a.fi_todo_order) - parseInt(b.fi_todo_order));

                            data.forEach((todo) => {
                                if (todo.fi_todo_done == "0") {
                                    self.assignedToMe.push(todo);
                                } else {
                                    self.assignedToMeDone.push(todo);
                                }
                            });
                        }
                        self.listMode = false;
                        self.kanbanMode = false;
                        self.assignedToMeMode = true; //mostro visualizzazione miei todo, nascondo blocco centrale "attuale"
                        self.selectedListName = "Assegnati a me";
                    });
            } catch (error) {
                console.error(error);
            } finally {
                this.isLoadingLists = false;
            }
        },
        /**
         * ! Get lists
         */
        getLists() {
            this.isLoadingLists = true;
            var self = this;
            try {
                const req = fetch(`${base_url}/todo-list/todo/getLists/${self.loggedUser}`)
                    .then((response) => response.json())
                    .then((data) => {
                        //console.log(data);
                        if (data.length != 0) {
                            data.forEach((list) => {
                                if (list.fi_todo_lists_created_by == self.loggedUser) {
                                    self.lists.push(list);
                                } else {
                                    self.sharedLists.push(list);
                                }
                            });
                        }
                        //elf.lists = data;
                    });
            } catch (error) {
                console.error(error);
            } finally {
                this.isLoadingLists = false;
            }
        },
        /**
         * ! Change list order
         */
        handleListDrag(event) {
            console.log(event);
            var self = this;
            var data = {
                listId: event.moved.element.fi_todo_lists_id,
                order: (event.moved.newIndex + 1).toString(),
                lists: self.lists,
            };

            const params = new URLSearchParams();
            params.append([token_name], token_hash);
            params.append("listOrder", JSON.stringify(data));

            /* for(var pair of params.entries()) {
                console.log(pair[0]+ ', '+ pair[1]);
            }
            return; */

            try {
                axios.post(`${base_url}todo-list/todo/changeListOrder/`, params).then(function (response) {
                    //console.log(response);
                    if (response.data.status == 1) {
                        console.log("ordine delle liste cambiato con successo");
                    } else {
                        console.log(self.error);
                        self.error = true;
                        self.errorMessage = response.data.txt;
                    }
                });
            } catch (error) {
                console.error(error);
            }
        },
        /**
         * ! Delete single todo
         */
        deleteList(listId) {
            var self = this;
            if (listId) {
                const params = new URLSearchParams();
                params.append([token_name], token_hash);

                try {
                    axios.post(`${base_url}todo-list/todo/deleteList/${listId}`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status == 1) {
                            self.lists = self.lists.filter((listItem) => listItem.fi_todo_lists_id != listId);
                            //svuoto tutto ciò che è correlato alla lista ed ai suoi todo
                            self.selectedList = null;
                            self.selectedListName = "";
                            self.todos = [];
                            //imposto a false il poter modificare la lista
                            this.canModifyList = false;
                            this.isListCreator = false;
                            self.doneTodos = [];
                            self.todosByCategory = [];
                            //svuoto localstorage dalla lista appena cancellata (era la selezionata)
                            localStorage.removeItem("selectedList");
                            //chiudo modale
                            self.showModal = false;
                        }
                    });
                } catch (error) {
                    console.error(error);
                }
            }
        },

        /**
         * ! Get todos for the clicked list
         */
        getTodo(listId, listName, listObject) {
            this.searchQuery = "";
            var self = this;
            this.isLoadingTodos = true;
            this.todos = [];
            this.doneTodos = [];
            this.showNewCategoryName = false;
            this.newCategoryName = "";
            //remove assignedToMeMode
            this.assignedToMeMode = false;
            this.listMode = true;
            this.kanbanMode = false;
            try {
                const req = fetch(`${base_url}todo-list/todo/getTodosList/${listId}`)
                    .then((response) => response.json())
                    .then((data) => {
                        //console.log(data);
                        /**
                         * ? Ordino i todo in base ad order e poi li inserisco negli array corretti
                         */
                        data.sort((a, b) => parseInt(a.fi_todo_order) - parseInt(b.fi_todo_order));

                        data.forEach((todo) => {
                            if (todo.fi_todo_done == "0") {
                                this.todos.push(todo);
                            } else {
                                this.doneTodos.push(todo);
                            }
                        });
                        this.selectedListObject = listObject;
                        this.selectedList = listId;
                        this.selectedListName = listName;
                        //se sono proprietario della lista posso modificarla e cancellarla
                        if (this.loggedUser == listObject.fi_todo_lists_created_by) {
                            this.canModifyList = true;
                        } else {
                            this.canModifyList = false;
                        }
                        this.editListUrl = base_url + "get_ajax/modal_form/edit-todo-list/" + this.selectedList;
                        this.deleteListUrl = base_url + "/db_ajax/generic_delete/fi_todo_lists/" + this.selectedList;
                        //save last clicked list to restore on page load
                        const lastList = {
                            id: this.selectedList,
                            name: this.selectedListName,
                            object: this.selectedListObject,
                        };
                        localStorage.setItem("selectedList", JSON.stringify(lastList));
                        //GET CATEGORIES FOR THE SELECTED LIST
                        self.getListCategories();
                        //GET TODO FORKANBAN VISUALIZATION FOR THE SELECTED LIST
                        try {
                            const req = fetch(`${base_url}todo-list/todo/getTodoBySublist/${listId}`)
                                .then((response) => response.json())
                                .then((data) => {
                                    //Order todos by done status
                                    data.forEach((column) => {
                                        if (column.todos.length != 0) {
                                            column.todos.sort((a, b) => parseInt(a.fi_todo_done) - parseInt(b.fi_todo_done));
                                        }
                                    });
                                    this.todosByCategory = data;
                                });
                        } catch (error) {
                            console.error(error);
                        }
                    });
            } catch (error) {
                console.error(error);
            } finally {
                this.isLoadingTodos = false;
            }
        },
        /**
         * ! Change todo order inside a single list
         */
        handleDrag(event) {
            //console.log(event)
            var self = this;
            var data = {
                todoId: event.moved.element.fi_todo_id,
                order: (event.moved.newIndex + 1).toString(),
                todos: self.todos,
            };

            const params = new URLSearchParams();
            params.append([token_name], token_hash);
            params.append("todoOrder", JSON.stringify(data));

            try {
                axios.post(`${base_url}todo-list/todo/changeTodoOrder/`, params).then(function (response) {
                    //console.log(response);
                    if (response.data.status == 1) {
                        console.log("ordine dei todo cambiato con successo");
                    } else {
                        console.log(self.error);
                        self.error = true;
                        self.errorMessage = response.data.txt;
                    }
                });
            } catch (error) {
                console.error(error);
            }
        },
        /*
        COMMENTO IN QUANTO CAMBIATA LA CHIAMATA, NON TORNO PIU IL TODO MODIFICATO MA SEMPLICEMENTE IMPOSTO NUOVAMENTE IL CAMPO ORDER DI TUTTI I TODO DELLA LISTA
        handleDrag(event) {
            //console.log(event)
            var self = this;
            var data = {
                todoId: event.moved.element.fi_todo_id,
                order: (event.moved.newIndex + 1).toString(),
                todos: self.todos,
            }

            const params = new URLSearchParams();
            params.append([token_name], token_hash);
            params.append('todoOrder', JSON.stringify(data));

            try {
                axios.post(`${base_url}todo-list/todo/changeTodoOrder/`, params)
                    .then(function(response) {
                        //console.log(response);
                        if (response.data.status == 1) {
                            //TESTING PER ORDINAMENTO 
                            //self.todos.map((item) => {
                                //console.log(item.fi_todo_text,'  ---  id: ', item.fi_todo_id, ' --- order: ', item.fi_todo_order)
                            //});
                            //cerco todo modificato e aggiorno il valore di order
                            const editedTodo = self.todos.find(todo => todo.fi_todo_id == response.data.data.fi_todo_id);
                            if (editedTodo) {
                                editedTodo.fi_todo_order = response.data.data.fi_todo_order;
                            }
                            //Cerco todo appena editato nell'array della kanban e lo aggiorno
                            self.todosByCategory.forEach(column => {
                                if (column.todos.length != 0) {
                                    const editedTodo = column.todos.find(todo => todo.fi_todo_id == response.data.data.fi_todo_id);
                                    const oldIndex = column.todos.findIndex(element => {
                                        return element.fi_todo_id == response.data.data.fi_todo_id;
                                    });
                                    // console.log(editedTodo);
                                    //console.log(oldIndex)
                                    if (editedTodo && oldIndex > -1) {
                                        editedTodo.fi_todo_order = response.data.data.fi_todo_order;
                                    }
                                }
                            });

                            //console.log(self.todos);
                            self.removeError();
                        } else {
                            console.log(self.error);
                            self.error = true;
                            self.errorMessage = response.data.txt;
                        }
                    });
            } catch (error) {
                console.error(error);
            }
        },*/

        /**
         * ! Set done todo
         */
        toggleDoneTodo(todoId, doneStatus) {
            var self = this;
            if (todoId && doneStatus) {
                const params = new URLSearchParams();
                params.append([token_name], token_hash);

                try {
                    axios.post(`${base_url}todo-list/todo/toggleDoneTodo/${todoId}/${doneStatus}`, params).then(function (response) {
                        //console.log(response);
                        /**
                         * Se fi_todo_done è 1 devo toglierlo da todo e spostarlo in doneTodo (e settare a false tutti quelli di todo altrimenti mi rimane il riferimento con il checked)
                         * Se fi_todo_fone è 0 devo toglierlo da doneTodo e spostarlo in todo (e settare a true tutti quelli di doneTodo altrimenti mi rimane il riferimento con il non checked)
                         */
                        if (response.data.data.fi_todo_done == "1") {
                            self.todos = self.todos.filter((todoObject) => todoObject.fi_todo_id != todoId);
                            //inserisco in doneTodos
                            self.doneTodos.push(response.data.data);
                            //idem per assignedToMe e inserisco in assignedToMeDone
                            self.assignedToMe = self.assignedToMe.filter((todoObject) => todoObject.fi_todo_id != todoId);
                            self.assignedToMeDone.push(response.data.data);
                        } else {
                            self.doneTodos = self.doneTodos.filter((todoObject) => todoObject.fi_todo_id != todoId);
                            //inserisco in todos
                            self.todos.push(response.data.data);
                            //idem per assignedToMeDone e inserisco in assignedToMe
                            self.assignedToMeDone = self.assignedToMeDone.filter((todoObject) => todoObject.fi_todo_id != todoId);
                            self.assignedToMe.push(response.data.data);
                        }
                        //Aggiorno lo stato del todo anche nella kanban
                        self.todosByCategory.forEach((column) => {
                            const editedTodo = column.todos.find((todo) => todo.fi_todo_id == response.data.data.fi_todo_id);
                            const oldIndex = column.todos.findIndex((element) => {
                                return element.fi_todo_id == response.data.data.fi_todo_id;
                            });
                            if (editedTodo && oldIndex > -1) {
                                editedTodo.fi_todo_done = response.data.data.fi_todo_done;
                            }
                        });

                        /* if (response.data.data.fi_todo_done == '1') {
                                //rimuovo da todos e inserisco in doneTodo
                                const index = self.todos.findIndex(element => {
                                    return element.fi_todo_id == response.data.data.fi_todo_id;
                                });
                                if (index > -1) {
                                    //trovato
                                    self.todos.splice(index, 1);
                                    self.todos.forEach(todoElement => {
                                        todoElement.fi_todo_done = '0';
                                    });
                                    self.doneTodos.push(response.data.data);
                                }
                            } else {
                                //rimuovo da doneTodos e inserisco in todo
                                const index = self.doneTodos.findIndex(element => {
                                    return element.fi_todo_id == response.data.data.fi_todo_id;
                                });
                                if (index > -1) {
                                    //trovato
                                    self.doneTodos.splice(index, 1);
                                    self.doneTodos.forEach(todoElement => {
                                        todoElement.fi_todo_done = '1';
                                    });
                                    self.todos.push(response.data.data);
                                }
                            } */
                    });
                } catch (error) {
                    console.error(error);
                }
            }
        },
        /**
         * ! Set favourite todo
         */
        toggleFavouriteTodo(todoId, currentStarredStatus) {
            /* console.log(todoId, currentStarredStatus);
            return; */
            var self = this;
            if (todoId && currentStarredStatus) {
                try {
                    const req = fetch(`${base_url}/todo-list/todo/setFavouriteTodo/${todoId}/${currentStarredStatus}`)
                        .then((response) => response.json())
                        .then((data) => {
                            //console.log(data);
                            if (data.status == 1) {
                                //cerco todo modificato e aggiorno il valore di starred
                                let editedTodo = null;
                                editedTodo = self.todos.find((todo) => todo.fi_todo_id == data.response.fi_todo_id);
                                if (editedTodo) {
                                    editedTodo.fi_todo_starred = data.response.fi_todo_starred;
                                }
                                editedTodo = self.doneTodos.find((todo) => todo.fi_todo_id == data.response.fi_todo_id);
                                if (editedTodo) {
                                    editedTodo.fi_todo_starred = data.response.fi_todo_starred;
                                }
                                self.removeError();
                            } else {
                                console.log(self.error);
                                self.error = true;
                                self.errorMessage = data.txt;
                            }
                        });
                } catch (error) {
                    console.error(error);
                }
            }
        },
        /**
         * ! Open sidebar with todo detail and form to edit
         */
        openTodoDetail(todo) {
            if (todo) {
                this.editingCategory = false;
                this.selectedCategory = null;
                this.selectedTodo = {
                    ...todo,
                };
                //this.showSidebar = !this.showSidebar;
                this.showSidebar = true;
                //se sono creatore todo posso modificarlo altrimenti no
                if (this.loggedUser == this.selectedTodo.fi_todo_created_by) {
                    this.canModifyTodo = true;
                } else {
                    this.canModifyTodo = false;
                }
                //Assegno oggetto di user a selectedUser
                let userObj = this.allUsers.find((user) => user.users_id == todo.fi_todo_assigned_to);
                //console.log("userObj: ", userObj);
                if (userObj) {
                    this.selectedUser = userObj;
                } else {
                    this.selectedUser = null;
                }
            }
        },
        /**
         * ! Add new todo
         */
        addTodo(list) {
            var self = this;
            if (this.newTodo.length != 0) {
                this.disabledInput = true;

                var data = {
                    userId: this.loggedUser,
                    listId: this.selectedList,
                    text: this.newTodo,
                };

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append("newTodo", JSON.stringify(data));

                try {
                    axios.post(`${base_url}todo-list/todo/createTodo/`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status == 1) {
                            self.todos.push(response.data.data);
                            self.newTodo = "";
                            self.removeError();
                            //devo aggiungerlo anche all'array della kanban nella colonna allTodo, se esiste
                            const column = self.todosByCategory.find((column) => column.columnId == 0);
                            if (column) {
                                column.todos.unshift(response.data.data);
                            } else {
                                self.todosByCategory.unshift({
                                    title: "All todo",
                                    columnId: "0",
                                    todos: new Array(response.data.data),
                                });
                            }
                            self.disabledInput = false;
                        } else {
                            console.log(self.error);
                            self.disabledInput = false;
                            self.error = true;
                            self.errorMessage = response.data.txt;
                        }
                    });
                } catch (error) {
                    console.error(error);
                    self.disabledInput = false;
                }
            }
        },
        /**
         * ! Create new list
         */
        createList() {
            if (this.newList.length != 0) {
                this.disabledInput = true;

                var self = this;

                var data = {
                    userId: this.loggedUser,
                    title: this.newList,
                };

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append("newList", JSON.stringify(data));
                try {
                    axios.post(`${base_url}todo-list/todo/createList/`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status == 1) {
                            self.lists.push(response.data.data);
                            self.newList = "";
                            self.removeError();
                        } else {
                            self.error = false;
                            self.errorMessage = null;
                        }
                        self.disabledInput = false;
                    });
                } catch (error) {
                    self.disabledInput = false;
                    console.error(error);
                }
            }
        },

        setAttachment() {
            console.log("file: ", this.$refs.fileInput);
        },
        handleFileUploads(event) {
            console.log(event);
            this.fileToUpload = event.target.files[0];
            this.fileName = this.fileToUpload.name;
        },

        /**
         * ! Update single ToDo
         */
        editTodo() {
            //console.log(this.fileToUpload);
            if (!this.canModifyTodo) {
                return;
            }

            var self = this;
            //per sicurezza svuoto di nuovo categoria selezionata
            self.selectedCategory = null;
            userSelect = this.selectedUser ? this.selectedUser.users_id : null;
            //console.log(userSelect);

            var data = {
                listId: this.selectedList,
                todoId: this.selectedTodo.fi_todo_id,
                text: this.selectedTodo.fi_todo_text,
                deadline: this.selectedTodo.fi_todo_deadline,
                reminder: this.selectedTodo.fi_todo_reminder,
                note: this.selectedTodo.fi_todo_note,
                category: this.selectedTodo.fi_todo_category,
                assignTo: userSelect,
            };

            /* const params = new URLSearchParams();
      params.append([token_name], token_hash);
      params.append("editedTodo", JSON.stringify(data));
      params.append("file", this.fileToUpload); */

            const formData = new FormData();
            formData.append([token_name], token_hash);
            formData.append("editedTodo", JSON.stringify(data));
            if (this.fileToUpload) {
                formData.append("fi_todo_file", this.fileToUpload);
            }

            /* for(var pair of params.entries()) {
                console.log(pair[0]+ ', '+ pair[1]);,
            }
      return; */

            try {
                axios.post(`${base_url}todo-list/todo/editTodo/`, formData).then(function (response) {
                    //console.log(response);
                    if (response.status == 200) {
                        //Se ho modificato uno in todos lo aggiorno
                        const editedTodo = self.todos.find((todo) => todo.fi_todo_id == response.data.data.fi_todo_id);
                        if (editedTodo) {
                            editedTodo.fi_todo_text = response.data.data.fi_todo_text;
                            editedTodo.fi_todo_deadline = response.data.data.fi_todo_deadline;
                            editedTodo.fi_todo_reminder = response.data.data.fi_todo_reminder;
                            editedTodo.fi_todo_note = response.data.data.fi_todo_note;
                            editedTodo.fi_todo_category = response.data.data.fi_todo_category;
                            editedTodo.fi_todo_assigned_to = response.data.data.fi_todo_assigned_to;
                            editedTodo.fi_todo_file = response.data.data.fi_todo_file ? response.data.data.fi_todo_file : null;
                            //close sidebar and reset selectedtodo value
                            self.showSidebar = false;
                            self.selectedTodo = null;
                            self.selectedUser = null;
                        }
                        /**
                         * ? 08/06/2022
                         * ?Commentato visot che invece di usare due array uso solo todos e sfrutto 2 computed per separarli tra fatti e non fatti
                         */
                        //se ho modificato uno in doneTodos lo aggiorno
                        const doneEditedTodo = self.doneTodos.find((todo) => todo.fi_todo_id == response.data.data.fi_todo_id);
                        if (doneEditedTodo) {
                            doneEditedTodo.fi_todo_text = response.data.data.fi_todo_text;
                            doneEditedTodo.fi_todo_deadline = response.data.data.fi_todo_deadline;
                            doneEditedTodo.fi_todo_reminder = response.data.data.fi_todo_reminder;
                            doneEditedTodo.fi_todo_note = response.data.data.fi_todo_note;
                            doneEditedTodo.fi_todo_category = response.data.data.fi_todo_category;
                            doneEditedTodo.fi_todo_assigned_to = response.data.data.fi_todo_assigned_to;
                            doneEditedTodo.fi_todo_file = response.data.data.fi_todo_file ? response.data.data.fi_todo_file : null;
                            //close sidebar and reset selectedtodo value
                            self.showSidebar = false;
                            self.selectedTodo = null;
                            self.selectedUser = null;
                        }

                        //Cerco todo appena editato nell'array della kanban e lo aggiorno
                        self.todosByCategory.forEach((column) => {
                            const editedTodo = column.todos.find((todo) => todo.fi_todo_id == response.data.data.fi_todo_id);
                            const oldIndex = column.todos.findIndex((element) => {
                                return element.fi_todo_id == response.data.data.fi_todo_id;
                            });
                            if (editedTodo && oldIndex > -1) {
                                editedTodo.fi_todo_text = response.data.data.fi_todo_text;
                                editedTodo.fi_todo_deadline = response.data.data.fi_todo_deadline;
                                editedTodo.fi_todo_reminder = response.data.data.fi_todo_reminder;
                                editedTodo.fi_todo_note = response.data.data.fi_todo_note;
                                editedTodo.fi_todo_category = response.data.data.fi_todo_category;
                                editedTodo.fi_todo_file = response.data.data.fi_todo_file ? response.data.data.fi_todo_file : null;
                                //close sidebar and reset selectedtodo value
                                self.showSidebar = false;
                                self.selectedTodo = null;
                                self.selectedUser = null;
                            }
                        });
                        /*                             var newColumn = null;
                            //Per i todos della kanban devo risalire al todo appena modificato, toglierlo dalla colonna attuale e metterlo in quella nuova
                            self.todosByCategory.forEach(column => {                                
                                const editedTodo = column.todos.find(todo => todo.fi_todo_id == response.data.data.fi_todo_id);
                                const oldIndex = column.todos.findIndex(element => {
                                    return element.fi_todo_id == response.data.data.fi_todo_id;
                                });
                                if (editedTodo && oldIndex > -1) {
                                    //identifico nuova colonna
                                    if (column.columnId == response.data.data.fi_todo_category) {
                                        newColumn = column;
                                    }
                                    console.log('vecchia colonna: ', column);
                                    console.log('vecchio indice del todo da rimuovere: ', oldIndex);
                                    console.log('todo in cui modificare category, toglierlo da vecchia colonna e metterlo in nuova: ', editedTodo);
                                    //devo toglierelo dalla vecchia colonna
                                    column.todos.splice(oldIndex, 1);
                                    //modificare la sua cateoggria
                                    editedTodo.fi_todo_text = response.data.data.fi_todo_text;
                                    editedTodo.fi_todo_deadline = response.data.data.fi_todo_deadline;
                                    editedTodo.fi_todo_reminder = response.data.data.fi_todo_reminder;
                                    editedTodo.fi_todo_note = response.data.data.fi_todo_note;
                                    editedTodo.fi_todo_category = response.data.data.fi_todo_category;
                                }
                                //const columnToRemoveTodo = self.todosByCategory.find(category => category.columnId == response.data.data.fi_todo_category);
                            });
                            //inserisco in nuova colonna
                            if (newColumn) {
                                newColumn.todos.push(response.data.data);
                            } */
                    }
                });
            } catch (error) {
                console.error(error);
            }
        },
        /**
         * ! Delete single todo
         */
        deleteTodo(todoId, todo) {
            if (!this.canModifyTodo) {
                return;
            }

            var self = this;
            if (todoId && todo) {
                const params = new URLSearchParams();
                params.append([token_name], token_hash);

                try {
                    axios.post(`${base_url}todo-list/todo/deleteTodo/${todoId}`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status == 1) {
                            /**
                             * Se fi_todo_done è 1 devo toglierlo da todo
                             * Se fi_todo_fone è 0 devo toglierlo da doneTodo
                             */

                            /**
                             * ? 08/06/2022
                             * ?Commentato visot che invece di usare due array uso solo todos e sfrutto 2 computed per separarli tra fatti e non fatti
                             */
                            /*                                 if (todo.fi_todo_done == '0') {
                                    //lo cerco in todos, se trovo lo rimuovo e chiudo sidebar
                                    const index = self.todos.findIndex(element => {
                                        return element.fi_todo_id == todo.fi_todo_id;
                                    });
                                    if (index > -1) {
                                        self.todos.splice(index, 1);
                                        self.showSidebar = false;
                                        self.selectedTodo = null;
                                    }
                                } else {
                                    //lo cerco in doneTodos, se trovo lo rimuovo e chiudo sidebar
                                    const index = self.doneTodos.findIndex(element => {
                                        return element.fi_todo_id == todo.fi_todo_id;
                                    });
                                    if (index > -1) {
                                        self.doneTodos.splice(index, 1);
                                        self.showSidebar = false;
                                        self.selectedTodo = null;
                                    }
                                } */
                            const index = self.todos.findIndex((element) => {
                                return element.fi_todo_id == todo.fi_todo_id;
                            });
                            if (index > -1) {
                                self.todos.splice(index, 1);
                                self.showSidebar = false;
                                self.selectedTodo = null;
                                self.selectedUser = null;
                            }
                            const doneIndex = self.doneTodos.findIndex((element) => {
                                return element.fi_todo_id == todo.fi_todo_id;
                            });
                            if (doneIndex > -1) {
                                self.doneTodos.splice(doneIndex, 1);
                                self.showSidebar = false;
                                self.selectedTodo = null;
                                self.selectedUser = null;
                            }
                            //Cerco il todo appena cancellato e lo rimuovo dalla kanban
                            self.todosByCategory.forEach((column) => {
                                const editedTodo = column.todos.find((element) => element.fi_todo_id == todo.fi_todo_id);
                                const oldIndex = column.todos.findIndex((element) => {
                                    return element.fi_todo_id == todo.fi_todo_id;
                                });
                                if (editedTodo && oldIndex > -1) {
                                    column.todos.splice(oldIndex, 1);
                                    self.showSidebar = false;
                                    self.selectedTodo = null;
                                    self.selectedUser = null;
                                }
                            });

                            //Se delete corretta chiudo sidebar (03/06/2022)
                            self.showSidebar = false;
                            self.selectedTodo = null;
                            self.selectedUser = null;
                        }
                    });
                } catch (error) {
                    console.error(error);
                }
            }
        },
        /**
         * ! Get todo category for the selected list
         */
        getListCategories() {
            var self = this;
            try {
                const req = fetch(`${base_url}/todo-list/todo/getListCategories/${self.selectedList}`)
                    .then((response) => response.json())
                    .then((data) => {
                        //console.log(data);
                        self.listCategories = data;
                    });
            } catch (error) {
                console.error(error);
            }
        },

        /**********************************************
         *
         * KANBAN MODE
         *
         *********************************************/
        /**
         * ! Change todo category on drag&drop (added event)
         */
        changeColumn(event, col, todoColumn) {
            var self = this;
            //se aggiungo in una colonna diversa dalla prima faccio operazioni
            if (event.added && col.columnId != 0) {
                const todo = event.added.element;
                const newColumnId = col.columnId;

                var data = {
                    column_id: newColumnId,
                    todo_id: todo.fi_todo_id,
                };

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append("todoInfo", JSON.stringify(data));

                axios.post(`${base_url}todo-list/todo/changeToDoCategory`, params).then(function (response) {
                    //console.log(response)
                    //Aggiorno il todo anche per la visualizzazione List (visto che è cambiao id categoria e sua label)
                    if (response.data.data.fi_todo_done == "0") {
                        //cerco in todos e aggiorno
                        const editedTodo = self.todos.find((todo) => todo.fi_todo_id == response.data.data.fi_todo_id);
                        if (editedTodo) {
                            editedTodo.fi_todo_category = response.data.data.fi_todo_category;
                            editedTodo.fi_todo_list_categories_name = response.data.data.fi_todo_list_categories_name;
                        }
                    } else {
                        //cerco in doneTodos e aggiorno
                        const editedTodo = self.doneTodos.find((todo) => todo.fi_todo_id == response.data.data.fi_todo_id);
                        if (editedTodo) {
                            editedTodo.fi_todo_category = response.data.data.fi_todo_category;
                            editedTodo.fi_todo_list_categories_name = response.data.data.fi_todo_list_categories_name;
                        }
                    }

                    /**
                     * ? 08/06/2022
                     * ?Commentato visot che invece di usare due array uso solo todos e sfrutto 2 computed per separarli tra fatti e non fatti
                     */
                    /*const editedTodo = self.todos.find(todo => todo.fi_todo_id == response.data.data.fi_todo_id);
                        if (editedTodo) {
                            editedTodo.fi_todo_category = response.data.data.fi_todo_category;
                            editedTodo.fi_todo_list_categories_name = response.data.data.fi_todo_list_categories_name;
                        }*/

                    if (response.data.status === 0) {
                        toastr.error(response.data.txt, {
                            timeOut: 2500,
                        });
                    }
                });
            } else {
                return false;
            }
        },
        /**
         * ! Shoe/hide input field to create new caegory
         */
        addNewCategory() {
            this.showNewCategoryName = !this.showNewCategoryName;
        },
        /**
         * ! Add new category to the visualizzed list
         */
        createNewCategory() {
            var self = this;
            if (this.newCategoryName.length != 0) {
                this.disabledInput = true;

                var data = {
                    list_id: this.selectedList,
                    category_name: this.newCategoryName,
                };

                const params = new URLSearchParams();
                params.append([token_name], token_hash);
                params.append("newCategory", JSON.stringify(data));

                try {
                    axios.post(`${base_url}todo-list/todo/createCategory`, params).then(function (response) {
                        //console.log(response);
                        if (response.data.status == 1) {
                            //Insert new category with correct format
                            self.todosByCategory.push({
                                title: response.data.data.fi_todo_list_categories_name,
                                columnId: response.data.data.fi_todo_list_categories_id,
                                todos: [],
                            });
                            //console.log(self.todosByCategory);
                            //Add new category {label, value} for v-select in todo detail
                            self.listCategories.push({
                                label: response.data.data.fi_todo_list_categories_name,
                                value: response.data.data.fi_todo_list_categories_id,
                            });
                            //Reset category fields
                            self.newCategoryName = "";
                            self.showNewCategoryName = false;
                            self.disabledInput = false;
                        } else {
                            self.disabledInput = false;
                            console.log(self.error);
                        }
                    });
                } catch (error) {
                    self.disabledInput = false;
                    console.error(error);
                }
            }
        },
    },
    computed: {
        /*
         * Return only not done todo
         */
        normalTodo() {
            /* var self = this;
            const normalTodo = this.todos.filter( todo => todo.fi_todo_done == '0');
            return normalTodo */
            var term = this.searchQuery.replace(/ /g, "");
            return this.todos.filter((todo) => {
                //todo con note
                if (todo.fi_todo_done == "0" && todo.fi_todo_note) {
                    return (
                        todo.fi_todo_text.toLowerCase().replace(/ /g, "").indexOf(term.toLowerCase().replace(/ /g, "")) != -1 ||
                        todo.fi_todo_note.toLowerCase().replace(/ /g, "").indexOf(term.toLowerCase().replace(/ /g, "")) != -1
                    );
                }
                //todo senza note
                if (todo.fi_todo_done == "0") {
                    return todo.fi_todo_text.toLowerCase().replace(/ /g, "").indexOf(term.toLowerCase().replace(/ /g, "")) != -1;
                }
            });
        },
        /*
         * Return only done todo
         */
        todoDone() {
            /*  var self = this;
            const todoDone = this.todos.filter( todo => todo.fi_todo_done == '1');
            return todoDone */
            var term = this.searchQuery.replace(/ /g, "");
            return this.todos.filter((todo) => {
                //todo con note
                if (todo.fi_todo_done == "1" && todo.fi_todo_note) {
                    return (
                        todo.fi_todo_text.toLowerCase().replace(/ /g, "").indexOf(term.toLowerCase().replace(/ /g, "")) != -1 ||
                        todo.fi_todo_note.toLowerCase().replace(/ /g, "").indexOf(term.toLowerCase().replace(/ /g, "")) != -1
                    );
                }
                //todo senza note
                if (todo.fi_todo_done == "1") {
                    return todo.fi_todo_text.toLowerCase().replace(/ /g, "").indexOf(term.toLowerCase().replace(/ /g, "")) != -1;
                }
            });
        },
        /*
         * Mapping assigned user id to name and last name
         */
        userMapping() {
            return (user_id) => {
                const assigned = this.allUsers.find((user) => user.users_id == user_id);
                if (assigned) {
                    return `${assigned.users_first_name} ${assigned.users_last_name}`;
                }
            };
        },
    },
    mounted() {
        this.getLists();
        //Restore last selected list and get its todos
        const lastSelectedList = JSON.parse(localStorage.getItem("selectedList"));
        if (lastSelectedList) {
            this.getTodo(lastSelectedList.id, lastSelectedList.name, lastSelectedList.object);
        }
        //Restore last selecte mode
        const todoMode = localStorage.getItem("todoMode");
        if (todoMode) {
            this.setMode(todoMode);
        }

        this.getAllUsers();
    },
});
