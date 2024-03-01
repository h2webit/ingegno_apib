<?php
$current_userdata = $this->auth->getSessionUserdata();
$userId = $current_userdata['users_id'];
?>

<style scoped>
* {
    font-family: 'Source Sans Pro', 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

.ghost-card {
    opacity: 0.75;
    background: #F7FAFC;
    border: 1px solid #4299e1 !important;
}

.mx-datepicker {
    width: 100% !important;
}

.mx-datepicker .mx-input-wrapper .mx-input {
    border: none;
}

.mx-input:disabled {
    width: 100%;
    height: 34px;
    padding: 6px 30px;
    padding-left: 10px;
    font-size: 14px;
    line-height: 1.4;
    color: #555 !important;
    background-color: #fff !important;
}

/* Todo header animation */
div.container_animation {
    width: 100%;
    height: 150px;
}

section.header_animation {
    position: relative;
    width: 100%;
    /*height: 100vh;*/
    height: 100%;
    background: #3586ff;
    overflow: hidden;
}

section.header_animation .wave {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 50px;
    background: url("<?php echo $this->layout->moduleAssets('todo-list', 'wave.png');?>");
    background-size: 1000px 50px;
}

section.header_animation .wave.wave1 {
    animation: animate 30s linear infinite;
    z-index: 100;
    opacity: 1;
    animation-delay: 0s;
    bottom: 0;

}

section.header_animation .wave.wave2 {
    animation: animate2 15s linear infinite;
    z-index: 99;
    opacity: 0.5;
    animation-delay: -5s;
    bottom: 10px;

}

section.header_animation .wave.wave3 {
    animation: animate3 30s linear infinite;
    z-index: 98;
    opacity: 0.2;
    animation-delay: 2s;
    bottom: 15px;

}

section.header_animation .wave.wave4 {
    animation: animate2 5s linear infinite;
    z-index: 97;
    opacity: 0.7;
    animation-delay: -5s;
    bottom: 10px;

}

@keyframes animate {
    0% {
        background-position-x: 0;
    }

    100% {
        background-position-x: 1000px;
    }
}

@keyframes animate2 {
    0% {
        background-position-x: 0;
    }

    100% {
        background-position-x: -1000px;
    }
}

.kanban-column-width {
    /*min-width: 250px;*/
    width: 300px
}

.fixed-kanban-column-width {
    width: 300px;
}

.custom_vueselect .vs__search::placeholder,
.custom_vueselect .vs__dropdown-toggle,
.custom_vueselect .vs__dropdown-menu {
    background: #ffffff;
    border: none;
    color: #000000;
}

.custom_vueselect .vs__clear,
.custom_vueselect .vs__open-indicator {
    fill: #8f8f8f;
}


audio,
canvas,
embed,
iframe,
img,
object,
svg,
video {
    display: initial !important;
    vertical-align: unset !important;
}

.dz-clickable {
    height: 200px;
    width: 100%;
    /*border: 1px solid red*/
    background-color: #ffffff;
    border-radius: 4px;
}

#customdropzone {
    background-color: #ffffff;
    letter-spacing: 0.2px;
    color: #777;
    transition: background-color .2s linear;
    height: 230px;
    padding: 16px;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    overflow-x: scroll;
}

#customdropzone .dz-preview {
    min-width: 120px;
    display: inline-block;
    margin-right: 16px;
    display: flex;
    margin-right: 16px;
    justify-content: flex-start;
    align-items: center;
    flex-direction: column;
}

#customdropzone .dz-preview .dz-image {
    width: 80px;
    height: 80px;
    /*margin-left: 40px;*/
    margin-bottom: 10px;
}

#customdropzone .dz-preview .dz-image>div {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    background-size: contain;
}

#customdropzone .dz-preview .dz-image>img {
    width: 100%;
    height: auto;
}

#customdropzone .dz-preview .dz-details {
    color: #000000;
    transition: opacity .2s linear;
    text-align: center;
}

#customdropzone .dz-success-mark,
.dz-error-mark {
    display: none;
}



form.dropform {
    display: block;
    height: 400px;
    width: 400px;
    background: #ccc;
    margin: auto;
    margin-top: 40px;
    text-align: center;
    line-height: 400px;
    border-radius: 4px;
}

div.file-listing {
    width: 400px;
    margin: auto;
    padding: 10px;
    border-bottom: 1px solid #ddd;
}

div.file-listing img {
    height: 100px;
    display: block;
}

div.remove-container {
    text-align: center;
}

div.remove-container a {
    color: red;
    cursor: pointer;
}

a.submit-button {
    display: block;
    margin: auto;
    text-align: center;
    width: 200px;
    padding: 10px;
    text-transform: uppercase;
    background-color: #CCC;
    color: white;
    font-weight: bold;
    margin-top: 20px;
}

progress {
    width: 400px;
    margin: auto;
    display: block;
    margin-top: 20px;
    margin-bottom: 20px;
}


.sidebar_custom {
    display: inherit;
}

.custom_sidebar_toggler {
    display: none;
}

/* For mobile phones: */
@media (max-width: 768px) {
    .sidebar_custom {
        display: none;
    }

    .custom_sidebar_toggler {
        display: inherit;
    }
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.4/tailwind.min.css" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/vue/2.6.14/vue.js"></script>
<!-- CDNJS :: Sortable (https://cdnjs.com/) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.13.0/Sortable.min.js"></script>
<!-- CDNJS :: Vue.Draggable (https://cdnjs.com/) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.24.3/vuedraggable.umd.min.js"></script>
<!-- AXIOS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.21.1/axios.min.js"></script>
<!-- MOMENT -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- Vue2-datepicker -->
<script src="https://cdn.jsdelivr.net/npm/vue2-datepicker@3.10.4/index.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vue2-datepicker@3.10.4/index.css">
<!-- Vue Select -->
<script src="https://unpkg.com/vue-select@latest"></script>
<link rel="stylesheet" href="https://unpkg.com/vue-select@latest/dist/vue-select.css">
<!-- Vue2Dropzone -->
<!-- <script src="https://cdn.jsdelivr.net/npm/vue2-dropzone@3.6.0/dist/vue2Dropzone.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vue2-dropzone@3.6.0/dist/vue2Dropzone.min.css"> -->

<!-- <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" /> -->


<div id="app">
    <div class="w-100 relative bg-white shadow-sm p-5 rounded-sm" style="min-height: 1000px;">
        <!-- LOADING -->
        <div v-if="isLoadingLists" class="absolute top-0 right-0 w-full h-full bg-white flex justify-center items-center">
            <svg class="animate-spin h-12 w-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- GRID PRINCIPALE -->
        <div class="grid grid-cols-12 gap-4 h-full" style="min-height: 800px;">
            <!-- COL SX - LISTS List -->
            <!-- <div ref="listContainer" class="col-span-12 md:col-span-3 bg-gray-100 p-10 h-full shadow-sm absolute top-0 left-0 bottom-0 md:static sidebar_custom" style="z-index: 110" :class="showListContainer ? 'static' : 'hidden'"> -->

            <div class="col-span-12 md:col-span-3 bg-gray-100 p-10 h-full shadow-sm">
                <p class="font-semibold font-sans tracking-wide text-4xl text-gray-900">
                    <?php e('Lists'); ?>
                </p>
                <!-- Assigned to me -->
                <div class="single_list flex justify-between p-5 bg-white rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer mt-12" @click="getAssignedToMe()">
                    <p class="font-semibold font-sans tracking-wide text-2xl text-gray-900 hover:text-blue-600 duration-300">
                        <i class="fas fa-list mr-3"></i> <?php e('Assigned to me');?>
                    </p>
                </div>

                <div class="lists_container flex flex-col mt-12">
                    <p class="font-semibold font-sans tracking-wide mb-6 text-2xl text-gray-900"><?php e('My Lists'); ?></p>
                    <!-- My list -->
                    <draggable :list="lists" @change="handleListDrag($event)" :animation="200" ghost-class="ghost-card" group="lists">
                        <div v-for="singleList in lists" :key="singleList.fi_todo_lists_id" class="single_list flex justify-between p-5 bg-white rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer" :class="singleList.fi_todo_lists_id == selectedList ? 'bg-blue-100' : ''" @click="getTodo(singleList.fi_todo_lists_id, singleList.fi_todo_lists_name, singleList)">
                            <p class="font-semibold font-sans tracking-wide text-2xl text-gray-900 hover:text-blue-600 duration-300" :style="{ color: singleList.fi_todo_lists_color }">
                                <i class="fas fa-list mr-3" :style="{ color: singleList.fi_todo_lists_color }"></i> {{ singleList.fi_todo_lists_name }}
                            </p>
                            <div v-if="singleList.fi_todo_lists_users && Object.keys(singleList.fi_todo_lists_users) && Object.keys(singleList.fi_todo_lists_users).length > 0" class="text-gray-400">
                                <i class="fas fa-user-friends"></i>
                                <span> +{{ Object.keys(singleList.fi_todo_lists_users).length }}</span>
                            </div>
                        </div>
                    </draggable>
                </div>
                <!-- New list -->
                <div class="flex items-center p-5 bg-white rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer">
                    <input type="text" v-model="newList" placeholder="<?php e('Create new list'); ?>" class="font-sans tracking-wide placeholder-gray-400 text-gray-900 w-full rounded-sm focus:outline-none focus:border-blue-300 focus:ring-1 focus:ring-blue-300" @keyup.enter="createList" :disabled="disabledInput" />
                </div>

                <div class="mt-8" v-if="sharedLists.length != 0">
                    <p class="font-semibold font-sans tracking-wide mb-6 text-2xl text-gray-900"><?php e('Shared with me'); ?></p>
                    <div v-for="singleSharedList in sharedLists" :key="singleSharedList.fi_todo_lists_id" class="single_list flex justify-between p-5 bg-white rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer" @click="getTodo(singleSharedList.fi_todo_lists_id, singleSharedList.fi_todo_lists_name, singleSharedList)">
                        <p class="font-semibold font-sans tracking-wide text-2xl text-gray-900 hover:text-blue-600 duration-300" :class="singleSharedList.fi_todo_lists_id == selectedList ? 'text-blue-600' : ''" :style="{ color: singleSharedList.fi_todo_lists_color }">
                            <i class="fas fa-list mr-3" :style="{ color: singleSharedList.fi_todo_lists_color }"></i> {{ singleSharedList.fi_todo_lists_name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- COL CENTRALE - TODO List -->
            <!-- <div class="col-span-12 md:col-span-6 bg-white h-full shadow-sm" :class="showSidebar ? 'col-span-12 md:col-span-7' : 'col-span-12 md:col-span-9' "> -->
            <!-- <div class="bg-white h-full shadow-sm" :class="showSidebar ? 'col-span-12 md:col-span-6' : 'col-span-12 md:col-span-9' "> -->
            <div class="col-span-12 md:col-span-9 bg-white h-full shadow-sm">
                <!-- <div class="col-span-12 bg-white h-full shadow-sm" :class="showListContainer ? 'md:col-span-9' : 'md:col-span-12' "> -->
                <!-- Animation Header ontainer -->
                <div class="flex container_animation w-full">
                    <section class="header_animation">
                        <!-- Selected list info -->
                        <div class="flex items-center p-10 mb-12">
                            <!-- List info -->
                            <div class="flex font-semibold font-sans tracking-wide text-white w-full">
                                <div class="text-4xl mr-2 font-sans" v-if="!selectedList">To Do</div>
                                <div v-if="selectedListName != ''" class="flex">
                                    <div class="text-4xl mr-2 font-sans">{{ selectedListName }} </div>
                                    <div v-if="!assignedToMeMode && canModifyList" class="flex">
                                        <a :href="editListUrl" class="js_open_modal flex items-center ml-2">
                                            <i class="fas fa-fw fa-cog text-white hover:text-gray-500 duration-300"></i>
                                        </a>
                                        <button class="flex items-center ml-4" @click="toggleShowModal">
                                            <i class="fas fa-fw fa-trash text-white hover:text-red-600 duration-300"></i>
                                        </button>
                                    </div>

                                    <div v-if="selectedListObject && selectedListObject.fi_todo_lists_users && Object.keys(selectedListObject.fi_todo_lists_users) && Object.keys(selectedListObject.fi_todo_lists_users).length > 0" class="flex items-center ml-8 text-white text-lg">
                                        <span class="mr-2"><?php e('Shared with'); ?>: </span>
                                        <span v-for="(user, index) in Object.values(selectedListObject.fi_todo_lists_users)" :key="index" class="mr-2">
                                            {{user}}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <!-- Tabs -->
                            <ul v-if="!assignedToMeMode" class="flex justify-end">
                                <li>
                                    <div class="flex items-center justify-center block p-3 cursor-pointer" :class="listMode ? 'bg-white rounded' : '' " @click="setMode('listMode')">
                                        <i class="fas fa-fw fa-list-ul w-5 h-5 text-base" :class="listMode ? 'text-gray-400' : 'text-white' "></i>
                                        <span class="ml-3 text-base font-medium text-base" :class="listMode ? 'text-gray-900' : 'text-white' "><?php e('List'); ?></span>
                                    </div>
                                </li>
                                <li class="ml-4">
                                    <div class="flex items-center justify-center block p-3 cursor-pointer" :class="kanbanMode ? 'bg-white rounded' : '' " @click="setMode('kanbanMode')">
                                        <i class="fas fa-fw fa-columns w-5 h-5 text-base" :class="kanbanMode ? 'text-gray-400' : 'text-white' "></i>
                                        <span class="ml-3 text-base font-medium" :class="kanbanMode ? 'text-gray-900' : 'text-white' "><?php e('Board'); ?></span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="wave wave1"></div>
                        <div class="wave wave2"></div>
                        <div class="wave wave3"></div>
                        <div class="wave wave4"></div>
                    </section>
                </div>

                <!-- To Do Container -->
                <div class="p-10 h-full mb-24">
                    <!-- ASSIGNED TO ME (ONLY LIST) MODE -->
                    <div v-if="assignedToMeMode">
                        <!-- Error message -->
                        <div v-if="error" class="flex justify-between items-center p-5 text-2xl font-medium bg-red-400 text-white rounded-sm my-5 shadow-sm overflow-hidden">
                            <span>{{ errorMessage }}</span>
                            <i class="fas fa-times-circle" @click="removeError"></i>
                        </div>

                        <!-- Todo -->
                        <div v-if="assignedToMe.length != 0">
                            <draggable :list="assignedToMe" @change="handleDrag($event)" :animation="200" ghost-class="ghost-card" group="assignedToMe">
                                <div v-for="singleTodo in assignedToMe" :key="singleTodo.fi_todo_id" class="single_list p-5 bg-gray-100 rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="flex items-start flex-col md:w-4/5 w-full">
                                            <div class="w-full flex items-center">
                                                <input type="checkbox" name="todo_done" :value="singleTodo.fi_todo_done" @change="toggleDoneTodo(singleTodo.fi_todo_id, singleTodo.fi_todo_done)">
                                                <div class="flex items-center w-full ml-4" @click="openTodoDetail(singleTodo)">
                                                    <p class="font-sans tracking-wide text-2xl" :class="singleTodo.fi_todo_done == 1 ? 'text-gray-400 line-through' : 'text-gray-900'">
                                                        {{ singleTodo.fi_todo_text }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="md:w-1/5 md:mt-0 flex justify-end items-center w-full mt-4">
                                            <div class="flex justify-end text-sm mr-3" v-if="singleTodo.fi_todo_deadline || singleTodo.fi_todo_reminder">
                                                <div v-if="singleTodo.fi_todo_deadline">
                                                    <span class="text-sm font-semibold px-2.5 py-0.5 rounded" :class="isExpired(singleTodo.fi_todo_deadline)">
                                                        {{ formatDate(singleTodo.fi_todo_deadline) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <span v-if="singleTodo.fi_todo_category" class="bg-blue-100 text-blue-800 text-sm font-semibold px-2.5 py-0.5 rounded mr-3">
                                                {{ singleTodo.fi_todo_list_categories_name }}
                                            </span>
                                            <i class="fas fa-fw fa-bell mr-3" :class="singleTodo.fi_todo_reminder ? 'text-purple-400' : 'hidden'"></i>
                                            <i class="fas fa-fw fa-star" :class="singleTodo.fi_todo_starred == 1 ? 'text-yellow-600' : ''" @click="toggleFavouriteTodo(singleTodo.fi_todo_id, singleTodo.fi_todo_starred)"></i>
                                        </div>
                                    </div>
                                </div>
                            </draggable>
                        </div>
                        <div v-else>
                            <p class="font-semibold font-sans tracking-wide mb-6 text-2xl text-gray-900"><?php e('No todo to display'); ?></p>
                        </div>

                        <!-- Done todo -->
                        <div v-if="assignedToMeDone.length != 0" class="pb-12">
                            <p class="font-semibold font-sans tracking-wide mb-6 text-2xl text-gray-900"><?php e('Done To Do'); ?></p>
                            <div v-for="singleDoneTodo in assignedToMeDone" :key="singleDoneTodo.fi_todo_id" class="single_list p-5 bg-gray-100 rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer">
                                <div class="flex flex-col md:flex-row">
                                    <div class="flex items-center w-4/5">
                                        <input type="checkbox" name="todo_done" :value="singleDoneTodo.fi_todo_done" :checked="singleDoneTodo.fi_todo_done == 1 ? true : false" @change="toggleDoneTodo(singleDoneTodo.fi_todo_id, singleDoneTodo.fi_todo_done)">
                                        <div class="flex items-center w-full ml-4" @click="openTodoDetail(singleDoneTodo)">
                                            <p class="font-sans tracking-wide text-2xl" :class="singleDoneTodo.fi_todo_done == 1 ? 'text-gray-400 line-through' : 'text-gray-900'">
                                                {{ singleDoneTodo.fi_todo_text }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="md:w-1/5 md:mt-0 flex justify-end items-center w-full mt-4">
                                        <!-- {{ singleDoneTodo.fi_todo_list_categories_name }}
                                        {{singleDoneTodo.fi_todo_category}} -->
                                        <span v-show="singleDoneTodo.fi_todo_category" class="bg-blue-100 text-blue-800 text-sm font-semibold px-2.5 py-0.5 rounded mr-3">
                                            {{ singleDoneTodo.fi_todo_list_categories_name }}
                                            {{singleDoneTodo.fi_todo_category}}
                                        </span>
                                        <i class="fas fa-fw fa-bell mr-3" :class="singleDoneTodo.fi_todo_reminder ? 'text-purple-400' : 'hidden'"></i>
                                        <i class="fas fa-fw fa-star" :class="singleDoneTodo.fi_todo_starred == 1 ? 'text-yellow-600' : ''" @click="toggleFavouriteTodo(singleDoneTodo.fi_todo_id, singleDoneTodo.fi_todo_starred)"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!--  LIST MODE -->
                    <div v-if="listMode">
                        <!-- Error message -->
                        <div v-if="error" class="flex justify-between items-center p-5 text-2xl font-medium bg-red-400 text-white rounded-sm my-5 shadow-sm overflow-hidden">
                            <span>{{ errorMessage }}</span>
                            <i class="fas fa-times-circle" @click="removeError"></i>
                        </div>

                        <!-- Todo -->
                        <div v-if="todos.length != 0">
                            <draggable :list="todos" @change="handleDrag($event)" :animation="200" ghost-class="ghost-card" group="todos">
                                <div v-for="singleTodo in todos" :key="singleTodo.fi_todo_id" class="single_list p-5 bg-gray-100 rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="flex items-start flex-col md:w-4/5 w-full">
                                            <div class="w-full flex items-center">
                                                <input type="checkbox" name="todo_done" :value="singleTodo.fi_todo_done" @change="toggleDoneTodo(singleTodo.fi_todo_id, singleTodo.fi_todo_done)">
                                                <div class="flex items-center w-full ml-4" @click="openTodoDetail(singleTodo)">
                                                    <p class="font-sans tracking-wide text-2xl" :class="singleTodo.fi_todo_done == 1 ? 'text-gray-400 line-through' : 'text-gray-900'">
                                                        {{ singleTodo.fi_todo_text }}
                                                    </p>
                                                </div>
                                            </div>
                                            <p v-if="singleTodo.fi_todo_assigned_to" class="w-full font-sans tracking-wide text-base text-grey-400 mt-4">
                                                <?php e('Assigned to');?>: {{ userMapping(singleTodo.fi_todo_assigned_to) }}
                                            </p>
                                        </div>
                                        <div class="md:w-1/5 md:mt-0 flex justify-end items-center w-full mt-4">
                                            <div class="flex justify-end text-sm mr-3" v-if="singleTodo.fi_todo_deadline || singleTodo.fi_todo_reminder">
                                                <div v-if="singleTodo.fi_todo_deadline">
                                                    <span class="text-sm font-semibold px-2.5 py-0.5 rounded" :class="isExpired(singleTodo.fi_todo_deadline)">
                                                        {{ formatDate(singleTodo.fi_todo_deadline) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <span v-if="singleTodo.fi_todo_category" class="bg-blue-100 text-blue-800 text-sm font-semibold px-2.5 py-0.5 rounded mr-3">
                                                {{ singleTodo.fi_todo_list_categories_name }}
                                            </span>
                                            <i class="fas fa-fw fa-bell mr-3" :class="singleTodo.fi_todo_reminder ? 'text-purple-400' : 'hidden'"></i>
                                            <i class="fas fa-fw fa-star" :class="singleTodo.fi_todo_starred == 1 ? 'text-yellow-600' : ''" @click="toggleFavouriteTodo(singleTodo.fi_todo_id, singleTodo.fi_todo_starred)"></i>
                                        </div>
                                    </div>
                                </div>
                            </draggable>
                        </div>
                        <div v-else>
                            <p class="font-semibold font-sans tracking-wide mb-6 text-2xl text-gray-900"><?php e('Select a list to view and create To Do'); ?></p>
                        </div>

                        <!-- New todo -->
                        <div class="new_todo p-5 bg-gray-100 rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer" v-if="selectedList">
                            <div class="flex items-center">
                                <div class="flex items-center w-full">
                                    <input type="text" v-model="newTodo" placeholder="<?php e('Write new todo'); ?>" class="font-sans tracking-wide placeholder-gray-400 text-gray-900 w-full rounded-sm focus:outline-none focus:border-blue-300 focus:ring-1 focus:ring-blue-300 w-full bg-gray-100" @keyup.enter="addTodo(selectedList)" :disabled="disabledInput" />
                                </div>
                            </div>
                        </div>

                        <!-- Done todo -->
                        <div v-if="doneTodos.length != 0" class="pb-12 pt-6">
                            <div class="w-full flex justify-between align-center mb-6">
                                <p class="font-semibold font-sans tracking-wide text-2xl text-gray-900"><?php e('Done To Do'); ?></p>
                                <span class="bg-blue-500 font-bold text-white text-base px-4 py-2 rounded cursor-pointer" @click="toggleDoneTodoVisibility()">{{ doneTodosVisible === true ? 'Nascondi' : 'Mostra' }}</span>
                            </div>

                            <div v-if="doneTodosVisible">
                                <div v-for="singleDoneTodo in doneTodos" :key="singleDoneTodo.fi_todo_id" class="single_list p-5 bg-gray-100 rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="flex items-center flex-col md:w-4/5 w-full">
                                            <div class="w-full flex items-center">
                                                <input type="checkbox" name="todo_done" :value="singleDoneTodo.fi_todo_done" :checked="singleDoneTodo.fi_todo_done == 1 ? true : false" @change="toggleDoneTodo(singleDoneTodo.fi_todo_id, singleDoneTodo.fi_todo_done)">
                                                <div class="flex items-center w-full ml-4" @click="openTodoDetail(singleDoneTodo)">
                                                    <p class="font-sans tracking-wide text-2xl" :class="singleDoneTodo.fi_todo_done == 1 ? 'text-gray-400 line-through' : 'text-gray-900'">
                                                        {{ singleDoneTodo.fi_todo_text }}
                                                    </p>
                                                </div>
                                            </div>
                                            <p v-if="singleDoneTodo.fi_todo_assigned_to" class="w-full font-sans tracking-wide text-base text-grey-400 mt-4" :class="singleDoneTodo.fi_todo_done == 1 ? 'text-gray-400' : 'text-gray-900'">
                                                <?php e('Assigned to');?>: {{ userMapping(singleDoneTodo.fi_todo_assigned_to) }}
                                            </p>
                                        </div>
                                        <div class="md:w-1/5 md:mt-0 flex justify-end items-center w-full mt-4">
                                            <span v-if="singleDoneTodo.fi_todo_category" class="bg-blue-100 text-blue-800 text-sm font-semibold px-2.5 py-0.5 rounded mr-3">
                                                {{ singleDoneTodo.fi_todo_list_categories_name }}
                                            </span>
                                            <i class="fas fa-fw fa-bell mr-3" :class="singleDoneTodo.fi_todo_reminder ? 'text-purple-400' : 'hidden'"></i>
                                            <i class="fas fa-fw fa-star" :class="singleDoneTodo.fi_todo_starred == 1 ? 'text-yellow-600' : ''" @click="toggleFavouriteTodo(singleDoneTodo.fi_todo_id, singleDoneTodo.fi_todo_starred)"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                    <!--  KANBAN MODE -->
                    <div v-if="kanbanMode">
                        <div v-if="todosByCategory.length != 0" class="h-full">
                            <div class="flex overflow-x-scroll h-full">
                                <div v-for="(column, index) in todosByCategory" :key="index" class="px-3 py-3">
                                    <div v-if="(column.todos.length != 0 && column.columnId == '0') || column.columnId != '0'">
                                        <div class="category_header flex justify-between items-center bg-gray-400 text-white px-4 py-3 kanban-column-width rounded-md mr-4 mb-4 cursor-pointer" @click="showEditCategory(column.columnId)">
                                            <p class="font-semibold font-sans tracking-wide text-2xl text-white capitalize">{{column.title}}</p>
                                            <div>
                                                <i v-if="column.columnId != '0'" class="fas fa-fw fa-plus mr-3 cursor-pointer" @click.stop="currentTodoToShow(column.columnId)"></i>
                                            </div>
                                        </div>
                                        <!-- New todo -->
                                        <div v-if="column.columnId != '0'">
                                            <todo-input :show-todo="showInput" :column="column.columnId" :current-todo="currentTodo" @add-todo-to-list="addToList"></todo-input>
                                        </div>

                                        <draggable :list="column.todos" @change="changeColumn($event, column, column.todos)" :animation="200" ghost-class="ghost-card" group="todosCategory" class="py-4 kanban-column-width rounded-md mr-4">
                                            <div v-for="singleTodo in column.todos" :key="singleTodo.fi_todo_id" class="single_list p-5 bg-gray-100 rounded-sm mb-5 shadow-sm overflow-hidden cursor-pointer">
                                                <div class="flex items-center">
                                                    <input type="checkbox" name="todo_done" :value="singleTodo.fi_todo_done" :checked="singleTodo.fi_todo_done == 1 ? true : false" @change="toggleDoneTodo(singleTodo.fi_todo_id, singleTodo.fi_todo_done)">
                                                    <div class="flex flex-col justify-start items-center w-full ml-4" @click="openTodoDetail(singleTodo)">
                                                        <p class="w-full font-sans tracking-wide text-2xl" :class="singleTodo.fi_todo_done == 1 ? 'text-gray-400 line-through' : 'text-gray-900'">
                                                            {{ singleTodo.fi_todo_text }}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex justify-between w-full mt-5 text-sm" v-if="singleTodo.fi_todo_deadline || singleTodo.fi_todo_reminder">
                                                    <div v-if="singleTodo.fi_todo_deadline">
                                                        Scadenza
                                                        <span class="text-sm font-semibold px-2.5 py-0.5 rounded ml-1" :class="isExpired(singleTodo.fi_todo_deadline)">
                                                            {{ formatDate(singleTodo.fi_todo_deadline) }}
                                                        </span>
                                                    </div>
                                                    <div v-if="singleTodo.fi_todo_reminder">
                                                        Reminder
                                                        <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-2.5 py-0.5 rounded ml-1">
                                                            {{ formatDateWithHours(singleTodo.fi_todo_reminder) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </draggable>
                                    </div>
                                </div>
                                <div class="px-3 py-3">
                                    <div class="category_header bg-blue-400 text-white px-4 py-3 kanban-column-width rounded-md mr-4 mb-4 cursor-pointer" @click="addNewCategory">
                                        <p class="font-semibold font-sans tracking-wide text-2xl text-white"><?php e('Add new column'); ?></p>
                                    </div>
                                    <input v-if="showNewCategoryName" type="text" v-model="newCategoryName" class="w-full p-4 mt-4 pr-12 text-md bg-gray-100 rounded-lg shadow-sm" placeholder="<?php e('Enter column name'); ?>" @keyup.enter="createNewCategory" :disabled="disabledInput">
                                </div>
                            </div>
                        </div>
                        <div v-else>
                            <p class="font-semibold font-sans tracking-wide mb-6 text-2xl text-gray-900"><?php e('Select a list to view and create To Do'); ?></p>
                        </div>
                        <!-- Creazione nel caso in cui todosByCategory è vuota -->
                        <div v-else class="px-3 py-3">
                            <div class="category_header bg-blue-400 text-white px-4 py-3 fixed-kanban-column-width rounded-md mr-4 mb-4 cursor-pointer" @click="addNewCategory">
                                <p class="font-semibold font-sans tracking-wide text-2xl text-white"><?php e('Add new column'); ?></p>
                            </div>
                            <input v-if="showNewCategoryName" type="text" v-model="newCategoryName" class="p-4 mt-4 pr-12 text-md bg-gray-100 rounded-lg shadow-sm fixed-kanban-column-width" placeholder="<?php e('Enter column name'); ?>" @keyup.enter="createNewCategory">
                        </div>
                    </div>
                </div>

            </div>

            <!-- COL DX - SIDEBAR -->
            <!-- <div class="col-span-12 md:col-span-2  h-full overflow-y-scroll shadow-sm" v-show="showSidebar"> -->
            <?php /*
            <div class="col-span-12 md:col-span-3  h-full overflow-y-scroll shadow-sm" v-show="showSidebar">
                <aside class="w-full h-full" aria-label="Sidebar">
                    <!-- Edit ToDo -->
                    <div class="overflow-y-auto py-8 px-6 bg-gray-100 rounded h-full">
                        <div class="flex justify-end">
                            <i class="fas fa-times cursor-pointer" @click="handleCloseSidebar(false)"></i>
                        </div>
                        <!-- Edit category -->
                        <div v-if="editingCategory && !selectedTodo">
                            <form @submit.prevent="editCategory">
                                <div class="mb-6">
                                    <label for="title" class="text-md font-medium"><?php e('Title'); ?></label>
            <div class="mt-1">
                <input type="text" v-model="selectedCategory.title" id="title" class="w-full p-4 pr-12 text-md border-gray-200 rounded-lg shadow-sm" placeholder="<?php e('Enter category title'); ?>">
            </div>
        </div>
        <div class="mb-6 flex justify-between">
            <button type="button" class="w-40 p-2 font-medium tracking-wide text-white bg-red-600 rounded-md shadow-sm" @click="deleteCategory(selectedCategory.columnId)"><?php e('Delete'); ?></button>
            <button type="submit" class="w-40 p-2 font-medium tracking-wide text-white bg-green-600 rounded-md shadow-sm"><?php e('Save'); ?></button>
        </div>
        </form>
    </div>
    <!-- Edit todo-->
    <div v-if="selectedTodo && !selectedCategory">
        <form @submit.prevent="editTodo">
            <div class="mb-6">
                <label for="title" class="text-md font-medium"><?php e('Title'); ?></label>
                <div class="mt-1">
                    <input type="text" v-model="selectedTodo.fi_todo_text" :readonly="!canModifyTodo" id="title" class="w-full p-4 pr-12 text-md border-gray-200 rounded-lg shadow-sm" placeholder="<?php e('Enter todo title'); ?>">
                </div>
            </div>
            <div class="mb-6">
                <label for="deadline" class="text-md font-medium"><?php e('Deadline'); ?></label>
                <div class="mt-1">
                    <date-picker v-model="selectedTodo.fi_todo_deadline" :disabled="!canModifyTodo" value-Type="format" class="w-full"></date-picker>
                </div>
            </div>
            <div class="mb-6">
                <label for="reminder" class="text-md font-medium"><?php e('Reminder'); ?></label>
                <div class="mt-1">
                    <date-picker v-model="selectedTodo.fi_todo_reminder" :disabled="!canModifyTodo" value-Type="format" type="datetime" :show-second="false" class="w-full"></date-picker>
                </div>
            </div>
            <div class="mb-6">
                <label for="title" class="text-md font-medium"><?php e('Assigned to'); ?></label>
                <v-select v-model="selectedUser" :options="allUsers" label="users_last_name" class="custom_vueselect">
                    <template slot="no-options">
                        Assegna utente
                    </template>
                    <template slot="option" slot-scope="option">
                        {{ option.users_first_name }} {{ option.users_last_name }}
                    </template>
                    <template slot="selected-option" slot-scope="option">
                        <div class="selected d-center">
                            {{ option.users_first_name }} {{ option.users_last_name }}
                        </div>
                    </template>
                </v-select>
            </div>
            <div class="mb-6">
                <label for="attachments" class="text-md font-medium block"><?php e('Attachment'); ?></label>
                <label for="inputItem" class="cursor-pointer mb-3 font-normal text-md bg-blue-100 text-blue-600 py-2 px-4 rounded shadow-sm">
                    <span v-if="selectedTodo.fi_todo_file">
                        <?php e('Change'); ?>
                    </span>
                    <span v-else>
                        <?php e('Choose file'); ?>
                    </span>
                    <!-- <input ref="fileInput" type="file" title=" " id="inputItem" class="btn_upload_file p-2 font-medium tracking-wide text-white bg-green-600 rounded-md shadow-sm" @change="handleFileUploads($event)" /> -->
                    <input ref="fileInput" type="file" title=" " id="inputItem" class="btn_upload_file hidden" @change="handleFileUploads($event)" />
                </label>
                <!-- <vue-dropzone ref="myvDropzone" id="customdropzone" :options="dropzoneOptions" :include-styling="false" v-on:vdropzone-thumbnail="thumbnail" v-on:vdropzone-sending="sendingEvent" @vdropzone-mounted="vmounted"></vue-dropzone> -->
                <div v-if="selectedTodo.fi_todo_file" class="mt-4">
                    <a :href="`${baseUrl}uploads/${selectedTodo.fi_todo_file}`" class="bg-white text-blue-600 rounded p-2 text-base font-medium" :class="canModifyTodo ? '' : 'opacity-40'" target="_blank">Scarica allegato</a>
                </div>
            </div>
            <div class="mb-6">
                <label for="note" class="text-md font-medium"><?php e('Note'); ?></label>
                <div class="mt-1">
                    <textarea v-model="selectedTodo.fi_todo_note" :readonly="!canModifyTodo" id="note" rows="10" class="w-full p-4 pr-12 text-md border-gray-200 rounded-lg shadow-sm" placeholder="<?php e('Enter todo note'); ?>"></textarea>
                </div>
            </div>
            <div class="mb-6 flex justify-between">
                <button type="button" class="w-40 p-2 font-medium tracking-wide text-white bg-red-600 rounded-md shadow-sm" :class="canModifyTodo ? '' : 'opacity-40' " @click="deleteTodo(selectedTodo.fi_todo_id, selectedTodo)"><?php e('Delete'); ?></button>
                <button type="submit" class="w-40 p-2 font-medium tracking-wide text-white bg-green-600 rounded-md shadow-sm" :class="canModifyTodo ? '' : 'opacity-40'"><?php e('Save'); ?></button>
            </div>
            <div v-if="!canModifyTodo" class="mb-6">
                <div class="bg-blue-100 rounded-sm p-3 text-blue-600 text-xl font-medium">
                    Non puoi modificare o cancellare todo create da altri
                </div>
            </div>
        </form>
    </div>
</div>
</aside>
</div>
*/
?>

</div>


<!-- MODAL / Edit todo -->
<div id="editModal" tabindex="-1" aria-hidden="true" class="flex justify-center pt-48 overflow-y-auto overflow-x-hidden absolute top-0 right-0 left-0 bottom-0 z-50 w-full md:inset-0 h-modal md:h-full bg-gray-800 bg-opacity-60" style="z-index: 110" v-show="showSidebar">
    <div class="relative p-4 w-full max-w-4xl h-full md:h-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex justify-between items-start p-4 rounded-t border-b">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <?php e('Edit'); ?>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" @click="showSidebar = false">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-6 space-y-6">
                <!-- Edit ToDo -->
                <div class="overflow-y-auto py-8 px-6 bg-gray-100 rounded h-full">
                    <!-- Edit category -->
                    <div v-if="editingCategory && !selectedTodo">
                        <form @submit.prevent="editCategory">
                            <div class="mb-6">
                                <label for="title" class="text-md font-medium"><?php e('Title'); ?></label>
                                <div class="mt-1">
                                    <input type="text" v-model="selectedCategory.title" id="title" class="w-full p-4 pr-12 text-md border-gray-200 rounded-lg shadow-sm" placeholder="<?php e('Enter category title'); ?>">
                                </div>
                            </div>
                            <div class="mb-6 flex justify-between">
                                <button type="button" class="w-40 p-2 font-medium tracking-wide text-white bg-red-600 rounded-md shadow-sm" @click="deleteCategory(selectedCategory.columnId)"><?php e('Delete'); ?></button>
                                <button type="submit" class="w-40 p-2 font-medium tracking-wide text-white bg-green-600 rounded-md shadow-sm"><?php e('Save'); ?></button>
                            </div>
                        </form>
                    </div>
                    <!-- Edit todo-->
                    <div v-if="selectedTodo && !selectedCategory">
                        <form @submit.prevent="editTodo">
                            <div class="mb-6">
                                <label for="title" class="text-md font-medium"><?php e('Title'); ?></label>
                                <div class="mt-1">
                                    <input type="text" v-model="selectedTodo.fi_todo_text" :readonly="!canModifyTodo" id="title" class="w-full p-4 pr-12 text-md border-gray-200 rounded-lg shadow-sm" placeholder="<?php e('Enter todo title'); ?>">
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="deadline" class="text-md font-medium"><?php e('Deadline'); ?></label>
                                <div class="mt-1">
                                    <date-picker v-model="selectedTodo.fi_todo_deadline" :disabled="!canModifyTodo" value-Type="format" class="w-full"></date-picker>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="reminder" class="text-md font-medium"><?php e('Reminder'); ?></label>
                                <div class="mt-1">
                                    <date-picker v-model="selectedTodo.fi_todo_reminder" :disabled="!canModifyTodo" value-Type="format" type="datetime" :show-second="false" class="w-full"></date-picker>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="title" class="text-md font-medium"><?php e('Assigned to'); ?></label>
                                <v-select v-model="selectedUser" :options="allUsers" label="users_last_name" class="custom_vueselect">
                                    <template slot="no-options">
                                        Assegna utente
                                    </template>
                                    <template slot="option" slot-scope="option">
                                        {{ option.users_first_name }} {{ option.users_last_name }}
                                    </template>
                                    <template slot="selected-option" slot-scope="option">
                                        <div class="selected d-center">
                                            {{ option.users_first_name }} {{ option.users_last_name }}
                                        </div>
                                    </template>
                                </v-select>
                            </div>
                            <div class="mb-6">
                                <label for="attachments" class="text-md font-medium block"><?php e('Attachment'); ?></label>
                                <label for="inputItem" class="cursor-pointer mb-3 w-full">
                                    <div class="flex justify-between">
                                        <span v-if="selectedTodo.fi_todo_file" class="bg-blue-100 text-blue-600 font-normal text-md py-2 px-4 rounded shadow-sm">
                                            <?php e('Change'); ?>
                                        </span>
                                        <span v-else class="bg-blue-100 text-blue-600 font-normal text-md py-2 px-4 rounded shadow-sm">
                                            <?php e('Choose file'); ?>
                                        </span>
                                        <span class="ml-4 font-medium">
                                            {{ fileName }}
                                        </span>
                                    </div>
                                    <!-- <input ref="fileInput" type="file" title=" " id="inputItem" class="btn_upload_file p-2 font-medium tracking-wide text-white bg-green-600 rounded-md shadow-sm" @change="handleFileUploads($event)" /> -->
                                    <input ref="fileInput" type="file" title=" " id="inputItem" class="btn_upload_file hidden" @change="handleFileUploads($event)" />
                                </label>
                                <!-- <vue-dropzone ref="myvDropzone" id="customdropzone" :options="dropzoneOptions" :include-styling="false" v-on:vdropzone-thumbnail="thumbnail" v-on:vdropzone-sending="sendingEvent" @vdropzone-mounted="vmounted"></vue-dropzone> -->
                                <div v-if="selectedTodo.fi_todo_file" class="mt-4 flex justify-between items-center">
                                    <a :href="`${baseUrl}uploads/${selectedTodo.fi_todo_file}`" class="bg-white text-blue-600 rounded p-2 text-base font-medium" :class="canModifyTodo ? '' : 'opacity-40'" target="_blank">Scarica allegato</a>
                                    <p class="text-black text-base font-medium">{{selectedTodo.fi_todo_file}}</p>
                                </div>
                            </div>
                            <div class="mb-6">
                                <label for="note" class="text-md font-medium"><?php e('Note'); ?></label>
                                <div class="mt-1">
                                    <textarea v-model="selectedTodo.fi_todo_note" :readonly="!canModifyTodo" id="note" rows="5" class="w-full p-4 pr-12 text-md border-gray-200 rounded-lg shadow-sm" placeholder="<?php e('Enter todo note'); ?>"></textarea>
                                </div>
                            </div>
                            <div class="mb-6 flex justify-between">
                                <button type="button" class="w-40 p-2 font-medium tracking-wide text-white bg-red-600 rounded-md shadow-sm" :class="canModifyTodo ? '' : 'opacity-40' " @click="deleteTodo(selectedTodo.fi_todo_id, selectedTodo)"><?php e('Delete'); ?></button>
                                <button type="submit" class="w-40 p-2 font-medium tracking-wide text-white bg-green-600 rounded-md shadow-sm" :class="canModifyTodo ? '' : 'opacity-40'"><?php e('Save'); ?></button>
                            </div>
                            <div v-if="!canModifyTodo" class="mb-6">
                                <div class="bg-blue-100 rounded-sm p-3 text-blue-600 text-xl font-medium">
                                    Non puoi modificare o cancellare todo create da altri
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- MODAL / Delete List -->
<div id="defaultModal" tabindex="-1" aria-hidden="true" class="flex justify-center pt-48 overflow-y-auto overflow-x-hidden absolute top-0 right-0 left-0 bottom-0 z-50 w-full md:inset-0 h-modal md:h-full bg-gray-800 bg-opacity-60" style="z-index: 110" v-if="showModal">
    <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex justify-between items-start p-4 rounded-t border-b">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                    <?php e('Delete list'); ?>
                </h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" @click="toggleShowModal">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-6 space-y-6">
                <p class="text-md leading-relaxed text-gray-500">
                    <?php echo e('Are you sure you want to delete this list?' ); ?>
                </p>
                <p class="text-md leading-relaxed text-gray-500">
                    <?php echo e('You can\'t undo this action.'); ?>
                </p>
            </div>
            <!-- Modal footer -->
            <div class="flex justify-end items-center p-6 space-x-2 rounded-b">
                <button type="button" class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-300 rounded-lg border border-gray-200 text-base font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 cursor-pointer" @click="toggleShowModal"><?php e('Cancel'); ?></button>
                <button type="button" class="text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-base px-5 py-2.5 text-center cursor-pointer" @click="deleteList(selectedList)"><?php e('Delete list'); ?></button>
            </div>
        </div>
    </div>
</div>


</div>
</div>


<script>
const userLogged = <?php echo $userId; ?>;

document.addEventListener('DOMContentLoaded', () => {
    let btn = document.querySelector('.sidebar-toggle');
    btn.click();
}, false);
</script>

<?php $this->layout->addModuleJavascript('todo-list', 'js/vue_init.js'); ?>