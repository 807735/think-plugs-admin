
const ruleText = `.layui-body::before { 
        position: absolute;
        width: 100%;
        height: 100%;
        content: "  ";
        background-image:url("{$css|raw}") !important; 
        z-index: 4 ;
        pointer-events: none;
     }`;
// 删除旧规则（简化版，实际需遍历所有样式表）
const styleSheets = document.styleSheets;
for (let sheet of styleSheets) {
    try {
        const rules = sheet.cssRules || sheet.rules;
        for (let i = 0; i < rules.length; i++) {
            if (rules[i].selectorText === '.layui-body::before') {
                sheet.deleteRule(i);
                break;
            }
        }
    } catch (e) { }
}

// 插入新规则
const styleSheet = styleSheets[0];
styleSheet.insertRule(ruleText, styleSheet.cssRules.length);