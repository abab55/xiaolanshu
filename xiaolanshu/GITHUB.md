# 上传到 GitHub 指南

仓库名：小蓝书（xiaolanshu）
协议：CC BY-NC 4.0（可用，不可商用）

## 步骤

1. 在 GitHub 创建新公开仓库（Public），名称：xiaolanshu / 小蓝书
2. 运行以下命令（已在本地初始化 git）：

```bash
git add .
git commit -m "feat: 小蓝书初始版本 — 瀑布流Feed、笔记详情、用户系统、互动、消息、聊天、好友系统、后台管理"
git remote add origin https://github.com/<你的GitHub用户名>/xiaolanshu.git
git branch -M main
git push -u origin main
```

3. 仓库已包含 LICENSE 文件（CC BY-NC 4.0）

## 重要说明

- 此代码为开源学习/研究用途，商业使用需联系作者授权
- 数据库（data/xiaolanshu.db）已在 .gitignore 中排除，不会上传
- 用户上传的图片（uploads/）也已排除，避免泄露用户内容
