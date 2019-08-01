module.exports = {
    el: '#app',

    /**
     * The application's data.
     */
    data: {
        projects: []
    },

    /**
     * Mount the component.
     */
    mounted() {
        this.getProjects();
    },

    methods: {
        /**
         * Get all of the projects for the current user.
         */
        getProjects() {
            axios.get('/api/projects')
                .then(response => {
                    this.projects = response.data;
                });
        }
    }
};
