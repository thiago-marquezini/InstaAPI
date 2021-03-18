# InstaAPI

API de Busca Segmentada no Instagram para seguir perfis que se encaixam no parametro de busca.
O objetivo é que os perfis seguidos sigam de volta criando uma base de seguidores segmentados.

O sistema de tarefas é capaz de deixar de seguir os perfis que nao seguiram de volta em um certo periodo de tempo pre-definido.

Funcoes principais (controllers/Insta.php):

- login() - Login to Instagram account and update its session.
- i2fauth() - Finish Two Factor Authentication.
- challenge() - Finish Challenge (0 = SMS, 1 = Email).
- search() - Search for people, hashtags and locations.
- discover() - Find hashtags (#), locations and people (@) related to a search query.
- related() - Get users related to another user.
- suggestions() - Get suggestions of users to follow based on interests, location etc.

Requerimentos:

- PHP
- CodeIgniter
