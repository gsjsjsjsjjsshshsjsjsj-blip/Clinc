#!/bin/bash

echo "================================================"
echo "   فحص حالة Git و GitHub 🔍"
echo "   Git & GitHub Status Check"
echo "================================================"
echo ""

echo "📍 الفرع الحالي (Current Branch):"
git branch --show-current
echo ""

echo "📊 حالة Git (Git Status):"
git status
echo ""

echo "📝 آخر 3 Commits:"
git log --oneline -3
echo ""

echo "📈 إحصائيات:"
echo "- عدد الملفات المتتبعة: $(git ls-files | wc -l)"
echo "- عدد Commits الكلي: $(git rev-list --count HEAD)"
echo ""

echo "🔄 المقارنة مع GitHub:"
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "✅ متزامن تماماً مع GitHub!"
    echo "   All changes are synced with GitHub!"
else
    echo "⚠️  هناك اختلافات مع GitHub"
    echo "   There are differences with GitHub"
    echo "   قم بعمل git push أو git pull"
fi
echo ""

echo "================================================"
echo "✅ انتهى الفحص!"
echo "================================================"
