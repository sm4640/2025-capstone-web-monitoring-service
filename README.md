# Web-Monitoring-Service

## 프로젝트 개요
 - 2025년 캡스톤 프로젝트 주제로 클라우드 인프라 장애 극복 AI 어시스턴트 플로우 구축을 하고 있다.
 - 장애를 감지하고 AI가 해결책을 제공해주는 과정에서 관리자가 그 해결책을 받아들일지에 대한 여부를 체크하고 기록을 확인하는 웹 서비스를 구현하고자 한다.

## 개발 스택
 - 백엔드, 프론트엔드 -> PHP / DB -> Postgresql
 - 웹 사이트의 디자인에 크게 신경쓰지 않고 완성도 있는 빠른 개발을 원하기 때문에 HTML 코드에 통합이 빠른 PHP를 활용하였다.
 - 로그 내용 부분의 수행 계획과 결과 부분이 복잡하기 때문에 복잡하고 구조화된 데이터 유형을 다루기 편리한 Postgresql을 활용하였다.

## 데이터베이스 구조
 - 유저
  - id(디폴트 순차 증가)
  - password
  - name
  - created_at
  - updated_at
 - 알람 내용
  - id(디폴트 순차 증가)
  - name
  - severity
  - instance
  - summary
  - create_at
  - updated_at
  - status
 - 로그 내용 → 한 알람에 여러 개 로그를 정렬할 때는 created_at으로
  - id(디폴트 순차 증가)
  - alert_id
  - feedback
  - plan
  - result
  - create_at
  - updated_at

## 기능 목록
 - 안 본 알람 목록 조회
 - 수행 중 알람 목록 조회
 - 수행된 알람 목록 조회
 - 안 본 알람 상세 조회
 - 수락/거절 + 피드백 기능
 - 수행중 알람 상세 조회
 - 수행된 알람 상세 조회