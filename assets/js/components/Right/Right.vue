<template>
    <div class="col-7 px-0">
        xd{{this.$route.params.id}}
        <div class="px-4 py-5 chat-box bg-white" ref="messagesBody">
            <template v-for="(message, index, key) in MESSAGES">
                <Message :message="message"/>
            </template>
        </div>

        <Input/>
    </div>
</template>

<script>
import {mapGetters} from 'vuex';
import Message from './Message'
import Input from './Input'
export default {
    data: () => ({
        eventSource: null
    }),
    components: {Message, Input},
    computed: {
            ...mapGetters(["HUBURL", "USERNAME"]),
            MESSAGES() {
                return this.$store.getters.MESSAGES(this.$route.params.id);
            }
    },
    methods: {
        scrollDown() {
            this.$refs.messagesBody.scrollTop = this.$refs.messagesBody.scrollHeight;
        },
        addMessage(data) {
            this.$store.commit("ADD_MESSAGE", {
                conversationId: this.$route.params.id,
                payload: data
            })
        }
    },
    mounted() {
        const vm = this;
        this.$store.dispatch("GET_MESSAGES", this.$route.params.id)
            .then(() => {
                this.scrollDown();
                if (vm.eventSource === null) {
                    let url = new URL(vm.HUBURL);
                    console.log("LISTEN TO", `/conversations/${vm.USERNAME}/${vm.$route.params.id}`)
                    url.searchParams.append('topic', `/conversations/${vm.USERNAME}/${vm.$route.params.id}`)
                    vm.eventSource = new EventSource(url, {
                        withCredentials: true
                    })
                    vm.eventSource.onmessage = function (event) {
                        vm.addMessage(JSON.parse(event.data))
                    }
                }
            })
    },
    watch: {
        MESSAGES: function (val) {
            this.$nextTick(() => {
                this.scrollDown();
            })
        }
    },
}
</script>