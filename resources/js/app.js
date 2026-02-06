import { Editor } from '@tiptap/core'
import StarterKit from '@tiptap/starter-kit'
import Placeholder from '@tiptap/extension-placeholder'
import TurndownService from 'turndown'
import { marked } from 'marked'

const turndown = new TurndownService({
    headingStyle: 'atx',
    hr: '---',
    bulletListMarker: '-',
    codeBlockStyle: 'fenced',
})

marked.setOptions({
    breaks: false,
    gfm: true,
})

window.tiptapEditorInit = (wireModelName = '') => {
    let editor = null
    let syncTimer = null

    return {
        init() {
            const initialContent = wireModelName ? (this.$wire?.[wireModelName] ?? '') : ''
            const html = initialContent ? marked.parse(initialContent) : ''

            editor = new Editor({
                element: this.$refs.editor,
                extensions: [
                    StarterKit.configure({
                        heading: { levels: [2, 3, 4] },
                    }),
                    Placeholder.configure({
                        placeholder: 'Write your answer here...',
                    }),
                ],
                content: html,
                editorProps: {
                    attributes: {
                        class: 'focus:outline-none',
                    },
                },
                onUpdate: ({ editor: e }) => {
                    if (!wireModelName || !this.$wire) {
                        return
                    }

                    clearTimeout(syncTimer)
                    syncTimer = setTimeout(() => {
                        const markdown = turndown.turndown(e.getHTML())
                        this.$wire.set(wireModelName, markdown, false)
                    }, 150)
                },
            })
        },

        isActive(type, attrs = {}) {
            return editor?.isActive(type, attrs) ?? false
        },

        toggleBold() { editor?.chain().focus().toggleBold().run() },
        toggleItalic() { editor?.chain().focus().toggleItalic().run() },
        toggleHeading(level) { editor?.chain().focus().toggleHeading({ level }).run() },
        toggleBulletList() { editor?.chain().focus().toggleBulletList().run() },
        toggleOrderedList() { editor?.chain().focus().toggleOrderedList().run() },
        toggleBlockquote() { editor?.chain().focus().toggleBlockquote().run() },
        toggleCodeBlock() { editor?.chain().focus().toggleCodeBlock().run() },
        toggleCode() { editor?.chain().focus().toggleCode().run() },
        setHorizontalRule() { editor?.chain().focus().setHorizontalRule().run() },

        setLink() {
            const previousUrl = editor?.getAttributes('link').href ?? ''
            const url = prompt('Enter URL:', previousUrl)
            if (url === null) {
                return
            }
            if (url === '') {
                editor?.chain().focus().extendMarkRange('link').unsetLink().run()
                return
            }
            editor?.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
        },

        destroy() {
            clearTimeout(syncTimer)
            editor?.destroy()
            editor = null
        },
    }
}
