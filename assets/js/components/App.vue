<template>
    <div class="container py-5 px-4">

        <div class="row rounded-lg overflow-hidden shadow">
            <Left/>
            <!-- Chat Box-->
            <router-view :key="$route.fullPath"></router-view>
        </div>
    </div>

</template>

<script>
import Left from './Left/Left'
import Conversation from './Left/Conversation'
import Right from './Right/Right'
export default {
    components: {Left, Right}
}
</script>