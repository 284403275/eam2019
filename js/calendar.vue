<template>
    <div ref="calendar" id="calendar"></div>
</template>

<script>
    import 'fullcalendar'
    // import 'fullcalendar-scheduler'
    import $ from 'jquery'

    export default {
        props: {
            events: {
                default() {
                    return []
                },
            },

            resources: {
                default() {
                    return []
                },
            },

            eventSources: {
                default() {
                    return []
                },
            },

            editable: {
                default() {
                    return true
                },
            },

            selectable: {
                default() {
                    return true
                },
            },

            selectHelper: {
                default() {
                    return true
                },
            },

            header: {
                default() {
                    return {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'month,agendaWeek,agendaDay'
                        // right: 'month,agendaWeek,agendaDay assignResources'
                    }
                },
            },

            defaultView: {
                default() {
                    return 'agendaWeek'
                },
            },

            sync: {
                default() {
                    return false
                }
            },

            config: {
                type: Object,
                default() {
                    return {}
                },
            },
        },

        computed: {
            defaultConfig() {
                const self = this
                return {
                    header: this.header,
                    defaultView: this.defaultView,
                    editable: this.editable,
                    selectable: this.selectable,
                    selectHelper: this.selectHelper,
                    views: {
                        timelineTenDay: {
                            buttonText: '10 Day',
                            type: 'timeline',
                            duration: { days: 10 }
                        }
                    },
                    customButtons: {
                        assignResources: {
                            text: 'Assign Resources',
                            click: function() {
                                self.$router.push('/resources')
                            }
                        }
                    },
                    buttonText: {
                        month: 'Month',
                        week: 'Week',
                        day: 'Day'
                    },
                    aspectRatio: 2,
                    timeFormat: 'HH:mm',
                    events: this.events,
                    eventSources: this.eventSources,
                    navLinks: true,
                    unselectAuto: false,
                    contentHeight: 'auto',
                    height: 'auto',

                    resources: this.resources,

                    viewRender(...args) {
                        self.$emit('view-render', ...args)
                    },

                    eventRender(...args) {
                        if (this.sync) {
                            self.events = cal.fullCalendar('clientEvents')
                        }
                        self.$emit('event-render', ...args)
                    },

                    eventDestroy(event) {
                        if (this.sync) {
                            self.events = cal.fullCalendar('clientEvents')
                        }
                    },

                    eventClick(...args) {
                        $('html, body').animate({
                            scrollTop: $(document).scrollTop()
                        }, 1);
                        self.$emit('event-selected', ...args)
                    },

                    eventDrop(...args) {
                        $('html, body').animate({
                            scrollTop: $(document).scrollTop()
                        }, 1);
                        self.$emit('event-drop', ...args)
                    },

                    eventReceive(...args) {
                        self.$emit('event-receive', ...args)
                    },

                    eventResize(...args) {
                        self.$emit('event-resize', ...args)
                    },

                    dayClick(...args) {
                        self.$emit('day-click', ...args)
                    },

                    select(start, end, jsEvent, view, resource) {
                        jsEvent.preventDefault();
                        self.$emit('event-created', {
                            start,
                            end,
                            allDay: !start.hasTime() && !end.hasTime(),
                            view,
                            resource
                        })
                        $('html, body').animate({
                            scrollTop: $(document).scrollTop()
                        }, 1);
                    },

                    unselect(...args) {
                        self.$emit('unselect', ...args)
                    }
                }
            },
        },

        mounted() {
            const cal = $(this.$el),
                self = this

            this.$on('remove-event', (event) => {
                if (event && event.hasOwnProperty('id')) {
                    $(this.$el).fullCalendar('removeEvents', event.id);
                } else {
                    $(this.$el).fullCalendar('removeEvents', event);
                }
            })

            this.$on('rerender-events', () => {
                $(this.$el).fullCalendar('rerenderEvents')
            })

            this.$on('refetch-resources', () => {
                this.config.resources = this.resources
            })

            this.$on('refetch-events', () => {
                $(this.$el).fullCalendar('refetchEvents')
            })

            this.$on('render-event', (event) => {
                $(this.$el).fullCalendar('renderEvent', event)
            })

            this.$on('reload-events', () => {
                $(this.$el).fullCalendar('removeEvents')
                $(this.$el).fullCalendar('addEventSource', this.events)
            })

            this.$on('rebuild-sources', () => {
                $(this.$el).fullCalendar('removeEventSources')
                this.eventSources.map(event => {
                    $(this.$el).fullCalendar('addEventSource', event)
                })
            })

            cal.fullCalendar(_.defaultsDeep(this.config, this.defaultConfig))
        },

        methods: {
            fireMethod(...options) {
                return $(this.$el).fullCalendar(...options)
            },
        },

        watch: {
            events: {
                deep: true,
                handler(val) {
                    $(this.$el).fullCalendar('removeEvents')
                    $(this.$el).fullCalendar('addEventSource', this.events)
                },
            },
            // resources: {
            //     deep: true,
            //     handler(val) {
            //         $(this.$el).fullCalendar('getResources').forEach((item) => {
            //             $(this.$el).fullCalendar('removeResource', item.id)
            //         })
            //
            //         val.forEach((item) => {
            //             $(this.$el).fullCalendar('addResource', {
            //                 id: item.id,
            //                 title: item.title
            //             })
            //         })
            //
            //         this.$nextTick(() => {
            //             $("#calendar").fullCalendar('rerenderEvents');
            //             // $(this.$el).fullCalendar('refetchResources')
            //             console.log('Next tick redraw')
            //         })
            //     },
            // },
            eventSources: {
                deep: true,
                handler(val) {
                    this.$emit('rebuild-sources')
                },
            },
        },

        beforeDestroy() {
            this.$off('remove-event')
            this.$off('rerender-events')
            this.$off('refetch-events')
            this.$off('render-event')
            this.$off('reload-events')
            this.$off('rebuild-sources')
        },
    }
</script>