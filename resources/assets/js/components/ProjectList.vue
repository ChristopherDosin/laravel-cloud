<template>
    <div>
        <div class="panel panel-default">
            <div class="panel-heading">Projects</div>

            <div class="panel-body">
                <div v-if="projects.length === 0">
                    You don't have any projects yet!
                </div>

                <div v-else>
                    <ul>
                        <li v-for="project in projects">
                            {{ project.name }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: ['projects'],

        data() {
            return {

            };
        },

        mounted() {
            //
        }
    }
</script>
