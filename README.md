# The Lightweight PHP Framework "Shooting-Star"

## 概要

自分用に作成した軽量PHPフレームワークです。

## 動作環境

PHP7.0以上  
MYSQL or MariaDB及び左記に必要なPHPパッケージ

## 主な特徴

- CakePHP2のような中途半端なMVCモデル
- CakePHPと同様のディレクトリ・コントローラー名から決定されるルーティング方式
- 最初からマスタスレーブ構成を意識したModel設計
- DBアクセスにおいてWHEREやJOINは全部自分で直接記述していく
- VC間の変数やりとりはバリデーションを含めて手軽に記述できる
- テンプレートエンジンなどはなくhtmlにPHP文を書き込んでいくスタイル
- 最低限の機能しかないので便利ライブラリ的なもののはcomposer requireして、どうぞ

## インストール

```bash
$ composer create-project sprteruyama/shooting-star-framework-app [app_dir] --prefer-dist 
```

## 実績

- とある大手動画配信サーバーの一部システムに採用されています。  
- とあるLINEのbotがこれで動いてます

