import {reactive, markRaw, toRefs} from 'vue'

export default function useReplaceableComponent(key, defaultComponent, props) {

    const state = reactive({
        component: markRaw(defaultComponent),
        props: props,
    })

    const setter = {
        set: (component, props = {}) => {
            state.component = component
            state.props = {
                ...state.props,
                ...props
            }
        }
    }

    // Emit event
    Statamic.$root.$emit(key, setter);

    return {
        ...toRefs(state),
        key,
    }

}